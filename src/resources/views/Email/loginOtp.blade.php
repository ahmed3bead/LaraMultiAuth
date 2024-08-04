@component('mail::message')
    Please use this OTP Code to verify your account.

    {{$token}}

    Thanks,
    {{ config('app.name') }}
@endcomponent
