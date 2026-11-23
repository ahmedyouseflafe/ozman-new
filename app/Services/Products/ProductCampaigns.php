<?php

namespace App\Services\Products;

use App\Models\Agent;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCampaign;
use App\Models\ProductImage;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** A product's ad campaigns (images/videos shown on screens and in the shop): add, change, remove. */
class ProductCampaigns
{
    public function __construct(private ProductMedia $media)
    {
    }

    public function storeCampaigns(Request $request, Product $product): \Illuminate\Support\Collection
    {
        $createdCampaigns = collect();

        foreach ($request->input('campaigns', []) as $index => $campaign) {
            $title = trim($campaign['title'] ?? '');
            $titleEn = trim($campaign['title_en'] ?? '');
            $titleHe = trim($campaign['title_he'] ?? '');
            $type = $campaign['type'] ?? null;
            $file = $request->file("campaigns.{$index}.media");
            $offerType = $campaign['offer_type'] ?? null;
            $unitKey = $campaign['unit_key'] ?? null;
            $offerQuantity = $campaign['offer_quantity'] ?? null;
            $minQuantity = $campaign['min_quantity'] ?? null;
            $maxQuantity = $campaign['max_quantity'] ?? null;
            $offerPrice = $campaign['offer_price'] ?? null;
            $offerNote = trim($campaign['offer_note'] ?? '');
            $offerNoteEn = trim($campaign['offer_note_en'] ?? '');
            $offerNoteHe = trim($campaign['offer_note_he'] ?? '');
            $startsAt = $campaign['starts_at'] ?? null;
            $endsAt = $campaign['ends_at'] ?? null;
            $hasOffer = filled($offerQuantity)
                || filled($minQuantity)
                || filled($maxQuantity)
                || filled($offerPrice)
                || $offerNote !== ''
                || filled($startsAt)
                || filled($endsAt);

            if ($title === '' && ! $file && ! $hasOffer) {
                continue;
            }

            if ($title === '' && $hasOffer) {
                $title = filled($offerQuantity) && filled($offerPrice)
                    ? "عرض {$offerQuantity} بسعر {$offerPrice}"
                    : 'عرض المنتج';
            }

            if ($title === '') {
                continue;
            }

            $media = null;
            if ($file) {
                if (! in_array($type, ['image', 'video'], true)) {
                    continue;
                }

                $this->validateCampaignFile($file, $type);

                $directory = $type === 'image' ? 'products/campaigns/images' : 'products/campaigns/videos';
                $path = $file->store($directory, 'public');
                $media = 'storage/' . $path;
            }

            $campaign = ProductCampaign::create([
                'product_id' => $product->id,
                'title' => $title,
                'title_translations' => array_filter([
                    'ar' => $title,
                    'en' => $titleEn,
                    'he' => $titleHe,
                ], fn($value) => filled($value)),
                'type' => $file ? $type : 'image',
                'media' => $media,
                'offer_type' => $offerType ?: null,
                'unit_key' => $unitKey ?: null,
                'offer_quantity' => filled($offerQuantity) ? (int) $offerQuantity : null,
                'min_quantity' => filled($minQuantity) ? (int) $minQuantity : null,
                'max_quantity' => filled($maxQuantity) ? (int) $maxQuantity : null,
                'offer_price' => filled($offerPrice) ? $offerPrice : null,
                'offer_note' => $offerNote !== '' ? $offerNote : null,
                'offer_note_translations' => array_filter([
                    'ar' => $offerNote,
                    'en' => $offerNoteEn,
                    'he' => $offerNoteHe,
                ], fn($value) => filled($value)),
                'starts_at' => $startsAt ?: null,
                'ends_at' => $endsAt ?: null,
                'is_active' => true,
            ]);
            $createdCampaigns->push($campaign);
        }

        return $createdCampaigns;
    }

    public function deleteCampaigns(Request $request, Product $product): void
    {
        $ids = collect($request->input('delete_campaign_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->all();

        if (empty($ids)) {
            return;
        }

        $campaigns = $product->campaigns()->whereIn('id', $ids)->get();

        foreach ($campaigns as $campaign) {
            $this->media->deleteUpload($campaign->media);
            $campaign->delete();
        }
    }

    public function updateCampaigns(Request $request, Product $product): void
    {
        $deletedIds = collect($request->input('delete_campaign_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->all();

        foreach ($request->input('existing_campaigns', []) as $campaignId => $campaignData) {
            $campaignId = (int) $campaignId;

            if (in_array($campaignId, $deletedIds, true)) {
                continue;
            }

            $campaign = $product->campaigns()->whereKey($campaignId)->first();
            if (! $campaign) {
                continue;
            }

            $title = trim($campaignData['title'] ?? '');
            $titleEn = trim($campaignData['title_en'] ?? '');
            $titleHe = trim($campaignData['title_he'] ?? '');
            $offerNote = trim($campaignData['offer_note'] ?? '');
            $offerNoteEn = trim($campaignData['offer_note_en'] ?? '');
            $offerNoteHe = trim($campaignData['offer_note_he'] ?? '');
            $type = $campaignData['type'] ?? $campaign->type;
            $unitKey = $campaignData['unit_key'] ?? null;
            $file = $request->file("existing_campaigns.{$campaignId}.media");

            if ($title === '') {
                $title = $campaign->title ?: (
                    filled($campaignData['offer_quantity'] ?? null) && filled($campaignData['offer_price'] ?? null)
                        ? "عرض {$campaignData['offer_quantity']} بسعر {$campaignData['offer_price']}"
                        : 'عرض المنتج'
                );
            }

            $updates = [
                'title' => $title,
                'title_translations' => array_filter([
                    'ar' => $title,
                    'en' => $titleEn,
                    'he' => $titleHe,
                ], fn($value) => filled($value)),
                'type' => in_array($type, ['image', 'video'], true) ? $type : $campaign->type,
                'offer_type' => $campaignData['offer_type'] ?? null,
                'unit_key' => $unitKey ?: null,
                'offer_quantity' => filled($campaignData['offer_quantity'] ?? null) ? (int) $campaignData['offer_quantity'] : null,
                'min_quantity' => filled($campaignData['min_quantity'] ?? null) ? (int) $campaignData['min_quantity'] : null,
                'max_quantity' => filled($campaignData['max_quantity'] ?? null) ? (int) $campaignData['max_quantity'] : null,
                'offer_price' => filled($campaignData['offer_price'] ?? null) ? $campaignData['offer_price'] : null,
                'offer_note' => $offerNote !== '' ? $offerNote : null,
                'offer_note_translations' => array_filter([
                    'ar' => $offerNote,
                    'en' => $offerNoteEn,
                    'he' => $offerNoteHe,
                ], fn($value) => filled($value)),
                'starts_at' => $campaignData['starts_at'] ?? null ?: null,
                'ends_at' => $campaignData['ends_at'] ?? null ?: null,
            ];

            if ($file) {
                $mediaType = $updates['type'];
                $this->validateCampaignFile($file, $mediaType);
                $this->media->deleteUpload($campaign->media);

                $directory = $mediaType === 'image' ? 'products/campaigns/images' : 'products/campaigns/videos';
                $path = $file->store($directory, 'public');
                $updates['media'] = 'storage/' . $path;
            }

            $campaign->update($updates);
        }
    }

    public function validateCampaignFile($file, string $type): void
    {
        $mime = $file->getMimeType();

        abort_if($type === 'image' && ! str_starts_with($mime, 'image/'), 422);
        abort_if($type === 'video' && ! str_starts_with($mime, 'video/'), 422);
    }
}
