<?php

namespace App\Mail;

use App\Models\BillingStatement;
use App\Models\MerchantBillingCard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BillingStatementMail extends Mailable
{
    use Queueable;

    public function __construct(
        public BillingStatement $statement,
        public ?MerchantBillingCard $card,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Edfundo Pay'),
            subject: "Your Edfundo Pay fees for {$this->statement->periodLabel()} ({$this->statement->number})",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.billing-statement');
    }
}
