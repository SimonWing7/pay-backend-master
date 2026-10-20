<?php

namespace App\Mail;

use App\Models\Merchant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent when Edfundo creates a merchant: a link to choose their own password. */
class MerchantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Merchant $merchant, public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Welcome to Edfundo Pay: set your password to get started',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.merchant-welcome');
    }
}
