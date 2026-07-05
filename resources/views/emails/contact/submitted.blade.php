<x-mail::message>
# New Contact Message

**From:** {{ $message->name }} ({{ $message->email }})  
**Subject:** {{ $message->subject }}

{{ $message->message }}

</x-mail::message>
