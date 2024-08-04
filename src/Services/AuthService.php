<?php

namespace AhmedEbead\LaraMultiAuth\Services;

use http\Env\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use AhmedEbead\LaraMultiAuth\Models\BaseAuthModel;
use AhmedEbead\LaraMultiAuth\Services\OtpService;
use Illuminate\Support\Facades\Password;

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

    protected static function getUserNameFields($guard)
    {
        $configModels = Config::get("multiauth.models.{$guard}");
        dd($configModels);
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
        $user = self::checkExistUser($credentials, $model, $guard);
        if (!$user) {
            return false;
        }
        if ($driver === 'passport') {
            return self::apiLogin($guard, $user);
        }
        return self::webLogin($guard, $user);
    }

    protected static function checkExistUser(array $credentials, BaseAuthModel $model, $guard)
    {
        $modelInstance = new $model;
        $authFields = Config::get("multiauth.guards.{$guard}.authFields");
        if (!$authFields) {
            $authFields = [
                'username' => ['email'],
                'password' => 'password'
            ];
        }

        // Find user by any of the username fields
        $query = $modelInstance->newQuery();
        foreach ($authFields['username'] as $field) {
            $query->orWhere($field, $credentials['username']);
        }
        $user = $query->first();
        if ($user && Hash::check($credentials['password'], $user->password)) {
            return $user;
        }
        return false;
    }

    private static function webLogin($guard, $user)
    {
        Auth::guard($guard)->setUser($user);
        return ['user' => Auth::guard($guard)->user()];
    }

    private static function apiLogin($guard, $user)
    {
        Auth::guard($guard)->setUser($user);
        return ['user' => Auth::guard($guard)->user(), 'token' => $user->createToken($guard)->accessToken];

    }

    public static function register(array $data)
    {
        $guard = self::getGuardForRequest();
        $modelClass = self::getModelClassForGuard($guard);
        $model = new $modelClass();
        $model->fill($data);
        $model->password = Hash::make($data['password']);
        $model->save();
        return $model;
    }

    public static function resetPassword(array $data)
    {
        $guard = self::getGuardForRequest();
        $modelClass = self::getModelClassForGuard($guard);
        $model = new $modelClass();
        $model = $model->where('email', $data['email'])->first();
        if ($model) {
            $model->password = Hash::make($data['password']);
            $model->save();
        }
        return $model;
    }

    public static function forgetPassword(string $email)
    {
        $request = new \Illuminate\Support\Facades\Request();
        $guard = self::getGuardForRequest();
        $modelClass = self::getModelClassForGuard($guard);
        $model = new $modelClass();
        $request->merge([
            'identifier' => $email // This will be either phone or email
        ]);

        $identifier = $request->input('identifier');

        // Determine if identifier is a phone number or email
        $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $model->where('email', $identifier)->first()
            : $model->where('phone', $identifier)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        // Generate a password reset token
        $token = Password::createToken($user);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            // Send reset link via email
            $user->notify(new ResetPasswordEmailNotification($token));
        } else {
            // Send reset link via SMS
            $user->notify(new ResetPasswordSmsNotification($token));
        }

        return response()->json(['message' => 'Reset link sent.'], 200);
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
}
