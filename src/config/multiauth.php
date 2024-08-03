<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Guard
    |--------------------------------------------------------------------------
    |
    | This option controls the default guard that will be used by the package.
    |
    */

    'default_guard' => env('MULTIAUTH_DEFAULT_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Models Configuration
    |--------------------------------------------------------------------------
    |
    | Define the model classes for each guard here. This allows for dynamic
    | resolution of models based on the configured guards.
    |
    */

    'models' => [
//        'web' => App\Models\WebUser::class,
//        'api' => App\Models\ApiUser::class,
        // Add more guards and their respective models here
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Helper Function
    |--------------------------------------------------------------------------
    |
    | Define the helper function used to send SMS. This function should be
    | available globally.
    |
    */

    'sms_helper_function' => env('SMS_HELPER_FUNCTION', 'sendSmsHelperFunction'),

];
