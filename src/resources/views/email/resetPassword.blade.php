<!-- resources/views/vendor/laramultiauth/email/resetPassword.blade.php -->

@component('mail::message')
    # Reset Password

    Your OTP code to reset your password is: {{ $token }}

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
