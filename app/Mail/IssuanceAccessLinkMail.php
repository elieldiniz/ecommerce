<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IssuanceAccessLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Finalize os dados do seu Certificado Digital',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.issuance-access-link',
        );
    }
}
