<?php

namespace AhmedEbead\LaraMultiAuth\Services;

use AhmedEbead\LaraMultiAuth\Enums\UserOtpNotifyTypes;
use AhmedEbead\LaraMultiAuth\Mails\SendMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use AhmedEbead\LaraMultiAuth\Models\BaseAuthModel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class BaseService
{
    protected static mixed $guard = null;

    public static function guard($guard): static
    {
        // Validate if the guard exists in the configuration
        if (!Config::has("auth.guards.{$guard}")) {
            throw new \InvalidArgumentException("Guard '{$guard}' is not defined in the `auth.php` configuration.");
        }

        self::$guard = $guard;
        return new static;
    }

    /**
     * @throws \Exception
     */
    protected static function getGuardForRequest()
    {
        if (self::$guard === null) {
            throw new \Exception('Guard must be set using the guard() method before calling any authentication methods.');
        }

        return self::$guard;
    }


    public static function generateAndSendOtp($identifier)
    {
        $otp = OtpService::generateOtp($identifier);
        if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $smsHelperFunction = Config::get('multiauth.sms_helper_function');
            if (function_exists($smsHelperFunction)) {
                return $smsHelperFunction($identifier, $otp);
            }
            throw new \Exception("SMS helper function not defined or does not exist.");
        } else {
            sendOtpToMail($identifier, $otp);
            return $otp;
        }
    }

    /**
     * @throws \Exception
     */
    protected static function getModelClassForGuard($guard)
    {

        $configModels = self::getGuardConfiguration($guard);

        if (!isset($configModels[$guard]['model'])) {
            throw new \Exception("You need to add model for guard {$guard} in package config file `multiauth.php`");
        }
        $modelClass = $configModels[$guard]['model'];
        if (!class_exists($modelClass)) {
            throw new \Exception("Model class $modelClass does not exist.");
        }

        return $modelClass;
    }


    private static function getGuardConfiguration($guard)
    {
        $guardConfiguration = Config::get("multiauth.guards");
        if (empty($guardConfiguration)) {
            throw new \Exception("You need to add guard {$guard} in package config file `multiauth.php`");
        }
        return $guardConfiguration;
    }

    protected static function sendOtpToMail($email, $otp): ?\Illuminate\Mail\SentMessage
    {
        return Mail::to($email)->send(new SendMail($otp));
    }

}
