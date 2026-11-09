<?php

namespace App\Mail;

use App\Models\CustomOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomOrderReceived extends Mailable
{
    public function __construct(public CustomOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New custom order request: '.$this->order->item_type);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.custom-order.received');
    }
}
