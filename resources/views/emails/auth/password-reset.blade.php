<x-mail::message>
# Password Reset Request

Hello {{ $userName }},

You are receiving this email because we received a password reset request for your account.

Your password reset token is:

<x-mail::panel>
{{ $resetToken }}
</x-mail::panel>

Or click the button below to reset your password:

<x-mail::button :url="$resetUrl">
Reset Password
</x-mail::button>

This password reset link will expire in {{ $expiresIn }}.

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

