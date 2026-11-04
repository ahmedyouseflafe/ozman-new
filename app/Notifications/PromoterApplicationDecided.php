<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Tells someone who applied as a promoter whether their page was accepted. */
class PromoterApplicationDecided extends Notification
{
    public function __construct(public bool $approved) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting(__('أهلًا :name', ['name' => Str::before(trim((string) $notifiable->name).' ', ' ')]));

        if ($this->approved) {
            return $mail->subject(__('تم قبول حسابك كمروّج · أوزمان'))
                ->line(__('مبروك! الإدارة قبلت طلبك، وصفحتك كمروّج صارت جاهزة.'))
                ->line(__('سجّل دخول وانشر أول ستوري أو فيديو عن محل بتحبه.'))
                ->action(__('افتح لوحتي'), route('promoter.dashboard'))
                ->salutation(__('فريق أوزمان'));
        }

        return $mail->subject(__('بخصوص طلبك كمروّج · أوزمان'))
            ->line(__('شكراً لاهتمامك. للأسف ما قدرنا نقبل طلبك كمروّج حالياً.'))
            ->line(__('حسابك ضل شغّال كعميل: بتقدر تتابع المروّجين وتجمع نقاط من المحلات.'))
            ->action(__('افتح حسابي'), route('community.account'))
            ->salutation(__('فريق أوزمان'));
    }
}
