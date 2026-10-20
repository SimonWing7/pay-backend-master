<?php

namespace App\Mail;

use App\Models\Merchant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Onboarding emails: 'submitted' goes to Edfundo, 'approved' and 'changes'
 * go to the merchant's owner login.
 */
class OnboardingStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Merchant $merchant,
        public string $kind,
        public ?string $note = null,
    ) {}

    public function envelope(): Envelope
    {
        $name = $this->merchant->merchant_trading_name ?: $this->merchant->name;

        $subject = match ($this->kind) {
            'submitted' => "Onboarding submitted for review: {$name}",
            'approved' => "You're live on Edfundo Pay",
            default => 'A few changes needed to finish setting up Edfundo Pay',
        };

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.onboarding-status');
    }
}
