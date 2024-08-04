@component('mail::message')
    # Reset Password
    Please use this OTP Code to Reset or change your password.

    {{$token}}

    Thanks
    {{ config('app.name') }}
@endcomponent
