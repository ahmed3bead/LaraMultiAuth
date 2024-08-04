<!-- resources/views/vendor/laramultiauth/email/loginOtp.blade.php -->

@component('mail::message')
    # Login OTP

    Your OTP code is: {{ $token }}

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
