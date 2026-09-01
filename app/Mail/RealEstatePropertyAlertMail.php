<?php

namespace App\Mail;

use App\Models\RealEstateAlert;
use App\Models\RealEstateProperty;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RealEstatePropertyAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RealEstateAlert $alert, public RealEstateProperty $property) {}

    public function envelope(): Envelope
    {
        $company = $this->property->shop;
        $companyName = trim((string) preg_replace('/[\r\n]+/', ' ', $company?->name ?: 'شركة عقارات'));
        $replyTo = filter_var($company?->email, FILTER_VALIDATE_EMAIL)
            ? [new Address($company->email, $companyName)]
            : [];

        return new Envelope(
            from: new Address((string) config('mail.from.address'), $companyName.' عبر Ozman'),
            replyTo: $replyTo,
            subject: 'عقار جديد يطابق بحثك: '.$this->property->localized('title'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.real_estate.property_alert');
    }
}
