<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\ApiContactRequest;
use App\Mail\ContactFormSubmitted;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends ApiController
{
    public function store(ApiContactRequest $request): JsonResponse
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

        return $this->success([
            'message' => 'Thank you! Your message has been sent.',
            'id' => $message->id,
        ], 201);
    }
}
