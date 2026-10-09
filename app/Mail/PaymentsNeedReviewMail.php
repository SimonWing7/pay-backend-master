<?php

namespace App\Mail;

use App\Models\Merchant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class PaymentsNeedReviewMail extends Mailable
{
    use Queueable;

    public function __construct(
        public Merchant $merchant,
        public Collection $payments,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->payments->count();

        return new Envelope(
            from: new Address(config('mail.from.address'), 'Edfundo Pay'),
            subject: "Action needed: {$count} " . ($count === 1 ? 'payment' : 'payments') . ' awaiting bank confirmation',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payments-need-review');
    }
}
