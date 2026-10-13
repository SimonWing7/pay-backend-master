<?php

namespace App\Mail;

use App\Models\BillingStatement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

class BillingChargeResultMail extends Mailable
{
    use Queueable;

    /** @param string $kind paid | retry | failed | awaiting_card */
    public function __construct(
        public BillingStatement $statement,
        public string $kind,
        public ?Carbon $retryAt = null,
    ) {}

    public function envelope(): Envelope
    {
        $period = $this->statement->periodLabel();

        $subject = match ($this->kind) {
            'paid'          => "Payment received: Edfundo Pay fees for {$period}",
            'retry'         => "We couldn't charge your card for your Edfundo Pay fees ({$period})",
            'failed'        => "Action needed: your Edfundo Pay fees payment failed ({$period})",
            'awaiting_card' => "Action needed: add a card to pay your Edfundo Pay fees ({$period})",
            default         => "Edfundo Pay fees for {$period}",
        };

        return new Envelope(
            from: new Address(config('mail.from.address'), 'Edfundo Pay'),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.billing-result');
    }
}
