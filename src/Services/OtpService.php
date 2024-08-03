<?php

namespace AhmedEbead\LaraMultiAuth\Services;

use Illuminate\Support\Facades\Config;
use Ichtrojan\Otp\Otp;

class OtpService
{
    public static function generateOtp($phone)
    {
        return Otp::generate($phone,'numeric', 6, 15);
    }

    public static function verifyOtp($phone, $otp)
    {
        return Otp::validate($phone, $otp);
    }

    public static function sendSms($phone, $otp)
    {
        $smsHelperFunction = Config::get('multiauth.sms_helper_function');
        if (function_exists($smsHelperFunction)) {
            return $smsHelperFunction($phone, $otp);
        }

        throw new \Exception("SMS helper function not defined or does not exist.");
    }
}
