<x-mail::message>
# Verify your email

Hello {{ $user->name }},

Use this one-time code to verify your {{ shop_name() }} account:

<x-mail::panel>
## {{ $otp }}
</x-mail::panel>

This code expires in **15 minutes**. If you did not create an account, you can ignore this email.

Thanks,<br>
{{ shop_name() }}
</x-mail::message>
