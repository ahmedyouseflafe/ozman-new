<?php

namespace App\Notifications;

use App\Models\MarketOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Emails for "المتجر الحر" orders: new order (seller), seller's answer (buyer), buyer cancelled (seller). */
class MarketOrderUpdate extends Notification
{
    public function __construct(public MarketOrder $order, public string $event) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $o = $this->order;
        $vars = ['number' => $o->order_number, 'title' => $o->listing_title, 'qty' => $o->quantity, 'total' => $o->formattedTotal()];
        $mail = (new MailMessage)->greeting(__('أهلًا :name', ['name' => Str::before(trim((string) $notifiable->name).' ', ' ')]))->salutation(__('فريق أوزمان'));

        return match ($this->event) {
            'new' => $mail->subject(__('طلب جديد على منتجك · :number', $vars))
                ->line(__('وصلك طلب على ":title": الكمية :qty، المجموع :total.', $vars))
                ->line(__('من: :name · :city', ['name' => $o->buyer_name, 'city' => $o->city]))
                ->action(__('افتح الطلبات'), route('market.sales'))
                ->line(__('اقبل الطلب أو ارفضه بأسرع وقت، الزبون ناطر ردك.')),
            'buyer_cancelled' => $mail->subject(__('الزبون ألغى الطلب · :number', $vars))
                ->line(__('الزبون ألغى طلبه على ":title".', $vars))
                ->action(__('افتح الطلبات'), route('market.sales')),
            default => $mail->subject(__('تحديث على طلبك · :number', $vars))
                ->line(__('طلبك على ":title" صار: :status.', [...$vars, 'status' => $o->statusLabel()]))
                ->when($o->status === MarketOrder::ACCEPTED, fn ($m) => $m->line(__('البائع رح يتواصل معك لترتيب التسليم والدفع.')))
                ->action(__('افتح طلباتي'), route('market.purchases')),
        };
    }
}
