<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\ContactMessageResource;
use App\Mail\ContactMessageReplyMail;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $messages = ContactMessage::withCount('replies')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('customer'), fn ($query) => $query->where('email', $request->customer))
            ->when($request->filled('status'), fn ($query) => match ($request->status) {
                'unread' => $query->where('is_read', false),
                'replied' => $query->whereNotNull('last_replied_at'),
                'unreplied' => $query->whereNull('last_replied_at'),
                default => $query,
            })
            ->latest()
            ->paginate(20);

        return $this->paginated($messages, ContactMessageResource::collection($messages));
    }

    public function show(ContactMessage $message): JsonResponse
    {
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        $message->load(['replies.admin', 'lastRepliedBy']);

        return $this->success(new ContactMessageResource($message));
    }

    public function reply(Request $request, ContactMessage $message): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $reply = ContactMessageReply::create([
            'contact_message_id' => $message->id,
            'admin_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        try {
            Mail::to($message->email)->send(new ContactMessageReplyMail($reply->load('contactMessage')));
            $reply->update(['sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Contact reply email failed: '.$e->getMessage(), [
                'message_id' => $message->id,
                'reply_id' => $reply->id,
            ]);
        }

        $message->update([
            'is_read' => true,
            'last_replied_at' => now(),
            'last_replied_by' => $request->user()->id,
        ]);

        return $this->success(new ContactMessageResource($message->fresh(['replies.admin'])));
    }

    public function destroy(ContactMessage $message): JsonResponse
    {
        $message->delete();

        return $this->success(['message' => 'Message deleted.']);
    }
}
