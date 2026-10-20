<?php

namespace App\Mail;

use App\Models\MerchantUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The one-time link for choosing a password: either an invitation to join a
 * merchant's account ('invite') or a forgotten-password reset ('reset').
 */
class TeamInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MerchantUser $member,
        public string $token,
        public string $kind = 'invite',
    ) {}

    public function businessName(): string
    {
        $merchant = $this->member->merchant;

        return $merchant?->merchant_trading_name ?: $merchant?->name ?: 'your team';
    }

    public function envelope(): Envelope
    {
        $subject = $this->kind === 'reset'
            ? 'Reset your Edfundo Pay password'
            : "You've been invited to {$this->businessName()} on Edfundo Pay";

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.team-invite');
    }
}
