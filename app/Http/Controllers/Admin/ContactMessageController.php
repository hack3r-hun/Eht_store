<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageReplyMail;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
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
            ->paginate(20)
            ->withQueryString();

        $customers = ContactMessage::selectRaw('email, max(name) as name, count(*) as messages_count, max(created_at) as latest_message_at')
            ->groupBy('email')
            ->orderByDesc('latest_message_at')
            ->limit(100)
            ->get();

        return view('admin.messages.index', compact('messages', 'customers'));
    }

    public function show(ContactMessage $message): View
    {
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        $message->load(['replies.admin', 'lastRepliedBy']);

        return view('admin.messages.show', compact('message'));
    }

    public function reply(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $reply = ContactMessageReply::create([
            'contact_message_id' => $message->id,
            'admin_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        $emailSent = false;

        try {
            Mail::to($message->email)->send(new ContactMessageReplyMail($reply->load('contactMessage')));
            $reply->update(['sent_at' => now()]);
            $emailSent = true;
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

        return redirect()
            ->route('admin.messages.show', $message)
            ->with(
                $emailSent ? 'success' : 'error',
                $emailSent
                    ? 'Reply saved and sent to the customer.'
                    : 'Reply saved, but the email could not be sent. Check mail settings and try again.'
            );
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', 'Message deleted.');
    }
}
