<?php

namespace App\Services;

use App\Models\{MarketListing, MarketListingImage, MarketOrder, PushDevice, User};
use App\Notifications\MarketOrderUpdate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\{Rule, ValidationException};

/**
 * "المتجر الحر" rules, shared by the website and the app: listing limits, stock taken when the
 * seller accepts, and who may do what. Problems are thrown as ValidationException, which the
 * website turns into "back with errors" and the app into a 422 message.
 */
class FreeMarket
{
    public function __construct(private MarketImage $images) {}

    /** Customers and promoters buy and sell; admins and staff only browse. */
    public function isMember(?User $user): bool
    {
        return (bool) ($user?->is_active && $user->earnsPoints());
    }

    public function validateListing(array $input, bool $creating): array
    {
        $data = Validator::make($input, [
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:0.5', 'max:1000000'],
            'category' => ['required', Rule::in(array_keys(config('market.categories')))],
            'condition' => ['required', Rule::in(array_keys(config('market.conditions')))],
            'city' => ['required', 'string', 'min:2', 'max:60'],
            'quantity' => ['required', 'integer', 'min:'.($creating ? 1 : 0), 'max:999'],
            ...MarketImage::rules(),
            'images' => [$creating ? 'required' : 'nullable', 'array', 'max:'.config('market.max_images', 6)],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ], [...MarketImage::messages(), 'images.required' => __('أضف صورة وحدة على الأقل للمنتج.'),
            'price.min' => __('السعر لازم يكون نص شيكل أو أكثر.'), 'quantity.min' => __('الكمية لازم تكون 1 أو أكثر.')])->validate();
        unset($data['images'], $data['remove_images']);
        $data['title'] = trim($data['title']);
        $data['city'] = trim($data['city']);

        return $data;
    }

    /** @param UploadedFile[] $files */
    public function createListing(User $seller, array $data, array $files): MarketListing
    {
        $active = MarketListing::where('user_id', $seller->id)->where('status', MarketListing::ACTIVE)->count();
        if ($active >= config('market.max_active_listings', 50)) {
            throw ValidationException::withMessages(['title' => __('وصلت للحد الأقصى من المنتجات المعروضة. اخفي أو احذف منتج قبل ما تضيف جديد.')]);
        }
        $paths = $this->storeImages($files);
        try {
            return DB::transaction(function () use ($seller, $data, $paths) {
                $listing = MarketListing::create([...$data, 'user_id' => $seller->id, 'status' => MarketListing::ACTIVE]);
                foreach ($paths as $i => $path) {
                    $listing->images()->create(['path' => $path, 'sort_order' => $i]);
                }

                return $listing;
            });
        } catch (\Throwable $e) {
            $this->images->delete($paths);
            throw $e;
        }
    }

    /** @param UploadedFile[] $files @param int[] $removeIds */
    public function updateListing(MarketListing $listing, array $data, array $files, array $removeIds): MarketListing
    {
        $listing->load('images');
        $remove = $listing->images->whereIn('id', array_map('intval', $removeIds));
        $keep = $listing->images->count() - $remove->count();
        $max = config('market.max_images', 6);
        if ($keep + count($files) > $max) {
            throw ValidationException::withMessages(['images' => __('بتقدر تضيف لحد :max صور.', ['max' => $max])]);
        }
        if ($keep + count($files) < 1) {
            throw ValidationException::withMessages(['images' => __('خلّي صورة وحدة على الأقل للمنتج.')]);
        }
        $paths = $this->storeImages($files);
        try {
            DB::transaction(function () use ($listing, $data, $paths, $remove) {
                // A sold-out listing comes back when the seller adds stock; the admin's removal stays.
                $status = match (true) {
                    $listing->status === MarketListing::SOLD && (int) $data['quantity'] > 0 => MarketListing::ACTIVE,
                    $listing->status === MarketListing::ACTIVE && (int) $data['quantity'] === 0 => MarketListing::SOLD,
                    default => $listing->status,
                };
                $listing->update([...$data, 'status' => $status]);
                MarketListingImage::query()->whereIn('id', $remove->pluck('id'))->delete();
                $next = (int) $listing->images()->max('sort_order') + 1;
                foreach ($paths as $i => $path) {
                    $listing->images()->create(['path' => $path, 'sort_order' => $next + $i]);
                }
            });
        } catch (\Throwable $e) {
            $this->images->delete($paths);
            throw $e;
        }
        $this->images->delete($remove->pluck('path')->all());

        return $listing->fresh('images');
    }

    public function toggleListing(MarketListing $listing): MarketListing
    {
        abort_if($listing->status === MarketListing::REMOVED, 403, __('الإدارة أوقفت هذا المنتج.'));
        $listing->update(['status' => $listing->status === MarketListing::ACTIVE ? MarketListing::HIDDEN : MarketListing::ACTIVE]);

        return $listing;
    }

    public function deleteListing(MarketListing $listing): void
    {
        abort_if($listing->orders()->where('status', 'accepted')->exists(), 422, __('في طلب مقبول على هالمنتج. سلّمه أو ألغيه أول.'));
        $paths = $listing->images()->pluck('path')->all();
        DB::transaction(function () use ($listing) {
            // Pending orders can't be fulfilled any more.
            $listing->orders()->where('status', 'pending')->update(['status' => 'cancelled']);
            $listing->delete();
        });
        $this->images->delete($paths);
    }

    public function placeOrder(User $buyer, MarketListing $listing, array $input): MarketOrder
    {
        abort_unless($this->isMember($buyer), 403, __('الطلب من المتجر الحر لحسابات العملاء والمروّجين.'));
        abort_unless($listing->isVisible(), 404);
        abort_if((int) $listing->user_id === (int) $buyer->id, 403, __('ما بتقدر تطلب منتجك.'));
        $data = Validator::make($input, [
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'buyer_name' => ['required', 'string', 'max:100'],
            'buyer_phone' => ['required', 'string', 'min:7', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'city' => ['required', 'string', 'min:2', 'max:60'],
            'address' => ['required', 'string', 'min:3', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ], ['buyer_phone.regex' => __('رقم الجوال لازم يكون أرقام بس.')])->validate();
        if ((int) $data['quantity'] > $listing->quantity) {
            throw ValidationException::withMessages(['quantity' => __('الكمية المتوفرة :count بس.', ['count' => $listing->quantity])]);
        }

        // Price is always the listing's current price, never a number from the form.
        $order = MarketOrder::create([
            ...$data,
            'order_number' => 'M'.now()->format('ymd').strtoupper(Str::random(5)),
            'market_listing_id' => $listing->id, 'seller_id' => $listing->user_id, 'buyer_id' => $buyer->id,
            'listing_title' => $listing->title, 'unit_price' => $listing->price,
            'total' => round((float) $listing->price * (int) $data['quantity'], 2), 'status' => MarketOrder::PENDING,
        ]);
        if (! $buyer->phone) {
            $buyer->forceFill(['phone' => $data['buyer_phone']])->save();
        }
        $this->notify($listing->seller, $order, 'new');

        return $order;
    }

    /** Seller: accept, reject, complete or cancel an order. */
    public function sellerAction(User $seller, MarketOrder $order, string $action): MarketOrder
    {
        abort_unless($seller->is_active && (int) $order->seller_id === (int) $seller->id, 403);
        abort_unless(in_array($action, ['accept', 'reject', 'complete', 'cancel'], true), 422);
        $allowed = ['accept' => [MarketOrder::PENDING], 'reject' => [MarketOrder::PENDING], 'complete' => [MarketOrder::ACCEPTED], 'cancel' => [MarketOrder::ACCEPTED]];

        $error = DB::transaction(function () use ($order, $action, $allowed) {
            $order = MarketOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($order->status, $allowed[$action], true)) {
                return __('حالة الطلب تغيّرت. حدّث الصفحة.');
            }
            $listing = $order->market_listing_id ? MarketListing::query()->lockForUpdate()->find($order->market_listing_id) : null;
            if ($action === 'accept') {
                // Stock is taken when the seller accepts, so two buyers can't get the last piece.
                if (! $listing || $listing->quantity < $order->quantity) {
                    return __('الكمية المتوفرة ما بتكفي لهالطلب. عدّل الكمية بالمنتج أو ارفض الطلب.');
                }
                $left = $listing->quantity - $order->quantity;
                $listing->update(['quantity' => $left, 'status' => $left === 0 && $listing->status === MarketListing::ACTIVE ? MarketListing::SOLD : $listing->status]);
                $order->update(['status' => MarketOrder::ACCEPTED, 'stock_taken' => true]);
            } elseif ($action === 'cancel') {
                if ($order->stock_taken && $listing) {
                    $listing->update(['quantity' => min(999, $listing->quantity + $order->quantity),
                        'status' => $listing->status === MarketListing::SOLD ? MarketListing::ACTIVE : $listing->status]);
                }
                $order->update(['status' => MarketOrder::CANCELLED, 'stock_taken' => false]);
            } else {
                $order->update(['status' => $action === 'reject' ? MarketOrder::REJECTED : MarketOrder::COMPLETED]);
            }

            return null;
        });
        if ($error) {
            throw ValidationException::withMessages(['order' => $error]);
        }
        $order->refresh();
        $this->notify($order->buyer, $order, $order->status);

        return $order;
    }

    /** Buyer: cancel while the seller hasn't answered yet. */
    public function buyerCancel(User $buyer, MarketOrder $order): MarketOrder
    {
        abort_unless($buyer->is_active && (int) $order->buyer_id === (int) $buyer->id, 403);
        $changed = MarketOrder::query()->whereKey($order->id)->where('status', MarketOrder::PENDING)->update(['status' => MarketOrder::CANCELLED]);
        if (! $changed) {
            throw ValidationException::withMessages(['order' => __('البائع رد على الطلب. تواصل معه لإلغائه.')]);
        }
        $order->refresh();
        $this->notify($order->seller, $order, 'buyer_cancelled');

        return $order;
    }

    /** @param UploadedFile[] $files */
    private function storeImages(array $files): array
    {
        $paths = [];
        try {
            foreach ($files as $file) {
                $paths[] = $this->images->store($file);
            }
        } catch (\Throwable $e) {
            $this->images->delete($paths);
            throw $e;
        }

        return $paths;
    }

    /** Email as before, plus a phone notification when the person has the app. */
    private function notify(?User $user, MarketOrder $order, string $event): void
    {
        if (! $user?->is_active) {
            return;
        }
        try {
            $user->notify(new MarketOrderUpdate($order, $event));
        } catch (\Throwable $e) {
            // The order is saved either way; a mail outage must not undo it.
            report($e);
        }
        try {
            $tokens = PushDevice::where('user_id', $user->id)->pluck('token');
            if ($tokens->isNotEmpty()) {
                [$title, $body] = match ($event) {
                    'new' => ['طلب جديد على منتجك 🛍️', "{$order->buyer_name} طلب {$order->quantity} × {$order->listing_title}"],
                    'buyer_cancelled' => ['الزبون ألغى الطلب', "الطلب {$order->order_number} على {$order->listing_title}"],
                    default => ['تحديث على طلبك', "{$order->listing_title}: {$order->statusLabel()}"],
                };
                app(FirebaseMessagingService::class)->sendToTokens($tokens, $title, $body, route('market.purchases'),
                    ['type' => in_array($event, ['new', 'buyer_cancelled'], true) ? 'market_sale' : 'market_purchase', 'market_order_id' => $order->id]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
