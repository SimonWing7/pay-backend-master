<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaymentLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        $merchant = $this->invoice->merchant;
        $brandName = $merchant?->merchant_trading_name ?: $merchant?->name ?: 'Edfundo Pay';
        $replyTo = $merchant?->notification_email ?: $merchant?->support_email;

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(config('mail.from.address'), "{$brandName} via Edfundo Pay"),
            replyTo: $replyTo ? [$replyTo] : [],
            subject: "Payment Request from {$brandName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-payment-link',
        );
    }
}
