<?php

use AhmedEbead\LaraMultiAuth\Mails\SendMail;
use Illuminate\Support\Facades\Mail;

if (!function_exists('sendSmsHelperFunction')) {
    function sendSmsHelperFunction($phone, $otp)
    {
        // Implement the SMS sending logic
    }
}

if (!function_exists('sendOtpToMail')) {
    function sendOtpToMail($email, $otp)
    {
        Mail::to($email)->send(new SendMail($otp));
    }
}

