# LaraMultiAuth Package Documentation (still under development)

*   [Introduction](#introduction)
*   [Installation](#installation)
*   [Configuration](#configuration)
*   [Usage](#usage)
*   [Models Implementation](#models-implementation)
*   [Helper Functions](#helper-functions)
*   [API and Web Authentication](#api-and-web-authentication)
*   [Examples](#examples)
*   [License](#license)

## 1\. Introduction

LaraMultiAuth is a Laravel package designed to handle comprehensive authentication features. It supports both API and web authentication, including multi-guard setups and OTP verification for email and phone.

## 2\. Installation

To install the `LaraMultiAuth` package, follow these steps:

1.  **Add the Package to Your Project**

    ```bash
    composer require ahmedebead/laramultiauth
    ```

## Setup

After installing the package, run the following command to complete the setup:

```bash
php artisan multiauth:setup
php artisan passport:install # If needed 

```

2.  **Register the Service Provider**

    Add the following line to the `providers` array in `config/app.php`:

    ```php
    AhmedEbead\LaraMultiAuth\LaraMultiAuthServiceProvider::class,
    ```

3.  **Publish the Configuration File**

    ```bash
    php artisan vendor:publish --provider="AhmedEbead\LaraMultiAuth\LaraMultiAuthServiceProvider"
    ```


## 3\. Configuration

Update the `config/multiauth.php` file to configure models and SMS helper functions:

```php
<?php

return [
    'guards' => [
        'web' => [
            'model' => App\Models\User::class,
            'authFields' => [
                'username' => ['email','name'], // any number of fields name to check
                'password' => 'password'
            ]
        ],
        // Add more guards and their respective models here
    ],

    'sms_helper_function' => env('SMS_HELPER_FUNCTION', 'sendSmsHelperFunction'),

];
```

## 4\. Usage

Use the package functions via the `LaraMultiAuth` facade:

```php
use AhmedEbead\LaraMultiAuth\Facades\LaraMultiAuth;

// Login
$token = LaraMultiAuth::guard('api')->guard('api')->login([
    'email' => 'user@example.com',
    'password' => 'password123',
]);

// Register
$user = LaraMultiAuth::guard('api')->register([
    'email' => 'newuser@example.com',
    'password' => 'password123',
]);

// Generate OTP
$otp = LaraMultiAuth::guard('api')->generateOtp('1234567890');

// Verify OTP
$isVerified = LaraMultiAuth::guard('api')->verifyOtp('1234567890', $otp);

// Generate and send OTP
$otp = LaraMultiAuth::guard('api')->generateAndSendOtp('1234567890');
```

## 5\. Models Implementation

**BaseAuthModel**

The `AhmedEbead\LaraMultiAuth\Models\BaseAuthModel` provides common authentication functionalities.

**Concrete Models**

Extend `BaseAuthModel` for each guard.

**Web User Model**

```php
<?php

namespace App\Models;

use AhmedEbead\LaraMultiAuth\Models\BaseAuthModel;

class WebUser extends BaseAuthModel
{
    protected $guardName = 'web';
}
```

**API User Model**

```php
<?php

namespace App\Models;

use AhmedEbead\LaraMultiAuth\Models\BaseAuthModel;

class ApiUser extends BaseAuthModel
{
    protected $guardName = 'api';
}
```

**Admin User Model**

```php
<?php

namespace App\Models;

use AhmedEbead\LaraMultiAuth\Models\BaseAuthModel;

class AdminUser extends BaseAuthModel
{
    protected $guardName = 'admin';
}
```

## 6\. Helper Functions

**Example SMS Helper Function**

Add this helper function to your project to handle SMS sending:

```php
if (!function_exists('sendSmsHelperFunction')) {
    function sendSmsHelperFunction($phone, $otp)
    {
        // Implement the SMS sending logic
        // Example: using an external SMS service
        // SmsService::send($phone, "Your OTP is: {$otp}");
    }
}
```



## 7\. API and Web Authentication

The package supports both API (using Laravel Passport) and web authentication. The guard type determines the authentication method:

*   **Web Authentication:** Uses standard session-based login.
*   **API Authentication:** Uses Laravel Passport for token-based login.

The authentication methods are dynamically handled based on the guard specified in the configuration.

## 8\. Examples

**Login with Email/phone or any another fields -- check config fields**

```php
// api is the guard you can change it to web or any another

$token = LaraMultiAuth::guard('api')->login([
    'username' => 'user@example.com',
    'password' => 'password123',
]);
```

**Register a New User**

```php
// api is the guard you can change it to web or any another

$user = LaraMultiAuth::guard('api')->register([
    'email' => 'newuser@example.com',
    'password' => 'password123',
    //.....other fields
]);
```

**Generate and Verify OTP**

```php
// api is the guard you can change it to web or any another

$otp = LaraMultiAuth::guard('api')->generateOtp('1234567890');
$isVerified = LaraMultiAuth::guard('api')->verifyOtp('1234567890', $otp);
```

**Generate and Send OTP**

```php
// api is the guard you can change it to web or any another
$otp = LaraMultiAuth::guard('api')->generateAndSendOtp('1234567890');
```

## 9\. License

The LaraMultiAuth package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more information.
