<?php

namespace AhmedEbead\LaraMultiAuth\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use AhmedEbead\LaraMultiAuth\Models\BaseAuthModel;
use AhmedEbead\LaraMultiAuth\Services\OtpService;

class AuthService
{
    protected static $guardName = null;

    public static function guard($guard)
    {
        // Validate if the guard exists in the configuration
        if (!Config::has("auth.guards.{$guard}")) {
            throw new \InvalidArgumentException("Guard '{$guard}' is not defined in the `auth.php` configuration.");
        }

        self::$guardName = $guard;
        return new static;
    }

    protected static function getGuardForRequest()
    {
        if (self::$guardName === null) {
            throw new \Exception('Guard must be set using the guard() method before calling any authentication methods.');
        }

        return self::$guardName;
    }

    public static function login(array $credentials)
    {

        $guard = self::getGuardForRequest();
        $modelClass = self::getModelClassForGuard($guard);

        $model = new $modelClass();
        return self::attemptLogin($credentials, $model, $guard);
    }

    private static function attemptLogin(array $credentials, BaseAuthModel $model, $guard)
    {
        $driver = Config::get("auth.guards.{$guard}.driver");

        if ($driver === 'passport') {
            return self::apiLogin($credentials, $model);
        }

        return self::webLogin($credentials, $guard);
    }

    private static function webLogin(array $credentials, $guard)
    {
        return Auth::guard($guard)->attempt($credentials);
    }

    private static function apiLogin(array $credentials, BaseAuthModel $model)
    {
        $user = $model::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            return $user->createToken('Personal Access Token')->accessToken;
        }

        return false;
    }

    public static function register(array $data)
    {
        $guard = self::getGuardForRequest();
        $modelClass = self::getModelClassForGuard($guard);
        $model = new $modelClass(null, $guard);
        $model->fill($data);
        $model->password = Hash::make($data['password']);
        $model->save();
        return $model;
    }

    public static function resetPassword(array $data)
    {
        $guard = self::getGuardForRequest();
        $modelClass = self::getModelClassForGuard($guard);
        $model = $modelClass::where('email', $data['email'])->first();
        if ($model) {
            $model->password = Hash::make($data['password']);
            $model->save();
        }
        return $model;
    }

    public static function forgetPassword(array $data)
    {
        // Handle the logic for forgetting the password
    }

    public static function generateOtp($phone)
    {
        return OtpService::generateOtp($phone);
    }

    public static function verifyOtp($phone, $otp)
    {
        return OtpService::verifyOtp($phone, $otp);
    }

    public static function generateAndSendOtp($phone)
    {
        $otp = OtpService::generateOtp($phone);
        $smsHelperFunction = Config::get('multiauth.sms_helper_function');
        if (function_exists($smsHelperFunction)) {
            $smsHelperFunction($phone, $otp);
        }
        return $otp;
    }

    private static function getModelClassForGuard($guard)
    {
        $configModels = Config::get("multiauth.models");
        if (empty($configModels)) {
            throw new \Exception("You need to add guard {$guard} model in package config file `multiauth.php`");
        }

        if (!isset($configModels[$guard])) {
            throw new \Exception("You need to add model for guard {$guard} in package config file `multiauth.php`");
        }
        $modelClass = $configModels[$guard];
        if (!class_exists($modelClass)) {
            throw new \Exception("Model class $modelClass does not exist.");
        }

        return $modelClass;
    }
}
