<x-mail::message>
# Verify Your Email Address

Hello {{ $userName }},

Thank you for registering! Please use the verification code below to verify your email address:

<x-mail::panel>
<div style="text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 8px;">
{{ $verificationCode }}
</div>
</x-mail::panel>

Simply enter this code in the app to complete your registration.

If you did not create an account, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

