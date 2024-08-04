<?php

namespace AhmedEbead\LaraMultiAuth\Services;

use Illuminate\Support\Facades\Config;
use Ichtrojan\Otp\Otp;

class OtpService
{
    /**
     * @throws \Exception
     */
    public static function generateOtp($phone)
    {
        return (new Otp)->generate($phone, 'numeric', 6, 15);
    }

    public static function verifyOtp($phone, $otp)
    {
        return (new Otp)->validate($phone, $otp);
    }

    public static function sendSms($identifier, $otp)
    {
        if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $smsHelperFunction = Config::get('multiauth.sms_helper_function');
            if (function_exists($smsHelperFunction)) {
                return $smsHelperFunction($identifier, $otp);
            }
            throw new \Exception("SMS helper function not defined or does not exist.");
        } else {
            sendOtpToMail($identifier, $otp);
            return true;
        }
    }
}
