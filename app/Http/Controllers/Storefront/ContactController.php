<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactFormSubmitted;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        return view('storefront.contact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $message = ContactMessage::create($request->validated());

        $adminEmail = shop_config('contact_email');

        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new ContactFormSubmitted($message));
            } catch (\Throwable $e) {
                Log::warning('Contact form email failed: '.$e->getMessage(), [
                    'message_id' => $message->id,
                ]);
            }
        }

        return redirect()
            ->route('contact')
            ->with('success', 'Thank you! Your message has been sent. We will get back to you soon.');
    }
}
