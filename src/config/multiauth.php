<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Models Configuration
    |--------------------------------------------------------------------------
    |
    | Define the model classes for each guard here. This allows for dynamic
    | resolution of models based on the configured guards.
    |
    */

    'guards' => [
        'web' => [
            'model' => \AhmedEbead\LaraMultiAuth\Models\User::class, // change to you user model
            'otp_notify_type' => 'email',
            'authFields' => [
                'username' => ['email', 'name'],
                'password' => 'password'
            ]
        ],
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
