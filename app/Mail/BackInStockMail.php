<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BackInStockMail extends Mailable
{
    public function __construct(public Product $product, public ProductVariant $variant) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->product->name.' is back in stock');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.stock.back-in-stock');
    }
}
