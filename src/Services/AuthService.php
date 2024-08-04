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

class AuthService
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

    /**
     * @throws \Exception
     */
    public static function login(array $credentials): bool|array
    {
        $guard = self::getGuardForRequest();
        $modelClass = self::getModelClassForGuard($guard);
        $model = new $modelClass();
        return self::attemptLogin($credentials, $model, $guard);
    }

    /**
     * @throws \Exception
     */
    public static function phoneLogin(mixed $phone): bool
    {
        $guard = self::getGuardForRequest();
        $modelClass = self::getModelClassForGuard($guard);
        $otp_notify_type = Config::get("multiauth.guards.{$guard}.otp_notify_type");
        $model = new $modelClass();
        $executed = RateLimiter::attempt('send-otp:' . $phone, $perMinute = 1, function () use ($phone, $modelClass, $otp_notify_type) {
            $otp = self::generateOtp($phone);
            $model = new $modelClass();
            $user = $model->where('phone', $phone)->first();
            if ($otp_notify_type == UserOtpNotifyTypes::SMS) {
                $smsHelperFunction = Config::get('multiauth.sms_helper_function');
                if (function_exists($smsHelperFunction)) {
                    $smsHelperFunction($phone, $otp);
                } else {
                    throw new \Exception("Please add sms_helper_function to config file and to sys helper functions");
                }
            } else {
                Mail::to($user->email)->send(new SendMail($otp, true));
            }
        });
        if (!$executed) {
            throw ValidationException::withMessages([
                'token' => [trans('auth.too_many_otp')],
            ]);
        }
        return true;
    }

    private static function attemptLogin(array $credentials, BaseAuthModel $model, $guard): bool|array
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

    protected static function checkExistUser(array $credentials, BaseAuthModel $model, $guard): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|bool
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

    private static function webLogin($guard, $user): array
    {
        Auth::guard($guard)->setUser($user);
        return ['user' => Auth::guard($guard)->user()];
    }

    private static function apiLogin($guard, $user): array
    {
        Auth::guard($guard)->setUser($user);
        return ['user' => Auth::guard($guard)->user(), 'token' => $user->createToken($guard)->accessToken];

    }

    /**
     * @throws \Exception
     */
    private static function verifyPhoneLogin($data): array
    {
        $otp = OtpService::verifyOtp($data['phone'], $data['otp']);

        if (!$otp->status) {
            throw ValidationException::withMessages([
                'token' => [$otp->message],
            ]);
        }

        $guard = self::getGuardForRequest();
        $modelClass = self::getModelClassForGuard($guard);
        $driver = Config::get("auth.guards.{$guard}.driver");
        $model = new $modelClass();

        $user = $model->where('phone', $data['phone'])->first();

        if ($driver === 'passport') {
            return self::apiLogin($guard, $user);
        }
        return self::webLogin($guard, $user);
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

    public static function forgetPassword($identifier)
    {
        $executed = RateLimiter::attempt('send-otp:' . $identifier, $perMinute = 1, function () use ($identifier) {
            $otp = self::generateOtp($identifier);
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
        });

        if (!$executed) {
            throw ValidationException::withMessages([
                'token' => [trans('auth.too_many_otp')],
            ]);
        }
        return $executed;
    }

    /**
     * @throws \Exception
     */
    public static function resetPassword(array $data)
    {
        $guard = self::getGuardForRequest();
        $modelClass = self::getModelClassForGuard($guard);

        $otp = OtpService::verifyOtp($data['identifier'], $data['otp']);
        if (!$otp->status) {
            throw ValidationException::withMessages([
                'token' => [trans('passwords.token')],
            ]);
        }
        $model = new $modelClass();
        if (isset($data['identifier_field_name'])) {
            $model = $model->where($data['identifier_field_name'], $data['identifier'])->first();
        } elseif (filter_var($data['identifier'], FILTER_VALIDATE_EMAIL)) {
            $model = $model->where('phone', $data['identifier'])->first();
        } else {
            $model = $model->where('phone', $data['identifier'])->first();
        }

        if ($model) {
            $model->password = Hash::make($data['password']);
            $model->save();
        }
        return $model;
    }


    /**
     * @throws \Exception
     */
    public static function loggedInUser(): array
    {
        return ['user' => Auth::guard(self::getGuardForRequest())->user()];
    }


    public static function generateOtp($phone)
    {
        return OtpService::generateOtp($phone);
    }

    public static function verifyOtp($phone, $otp)
    {
        return OtpService::verifyOtp($phone, $otp);
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

    private function sendMail($email): bool
    {
        $otp = self::generateOtp($email);
        sendOtpToMail($email, $otp);
        return true;
    }

    private function logout(): bool
    {
        $user = Auth::guard(self::getGuardForRequest())->user();
        $user->tokens()->delete();
        request()->session()->invalidate(); // Invalidate the session
        request() > session()->regenerateToken(); // Regenerate CSRF token
        return true;
    }
}
