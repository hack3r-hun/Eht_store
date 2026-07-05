<?php

namespace App\Mail;

use App\Models\ContactMessageReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessageReply $reply)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $contact = $this->reply->contactMessage;
        $replyTo = shop_config('contact_email') ?: config('mail.from.address');

        return new Envelope(
            replyTo: [new Address($replyTo, shop_name())],
            subject: 'Re: '.$contact->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact.reply',
        );
    }
}
