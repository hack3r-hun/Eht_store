@component('mail::message')
# Reply from {{ shop_name() }}

Hello {{ $reply->contactMessage->name }},

{!! nl2br(e($reply->message)) !!}

@component('mail::panel')
**Your original message**

Subject: {{ $reply->contactMessage->subject }}

{{ $reply->contactMessage->message }}
@endcomponent

Thanks,<br>
{{ shop_name() }}
@endcomponent
