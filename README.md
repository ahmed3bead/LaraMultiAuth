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

    ```
    composer require ahmedebead/laramultiauth
    ```

2.  **Register the Service Provider**

    Add the following line to the `providers` array in `config/app.php`:

    ```
    AhmedEbead\LaraMultiAuth\LaraMultiAuthServiceProvider::class,
    ```

3.  **Publish the Configuration File**

    ```
    php artisan vendor:publish --provider="AhmedEbead\LaraMultiAuth\LaraMultiAuthServiceProvider"
    ```


## 3\. Configuration

Update the `config/multiauth.php` file to configure models and SMS helper functions:

```
<?php

return [

    'default_guard' => env('MULTIAUTH_DEFAULT_GUARD', 'web'),

    'models' => [
        'web' => App\Models\WebUser::class,
        'api' => App\Models\ApiUser::class,
        'admin' => App\Models\AdminUser::class,
    ],

    'sms_helper_function' => env('SMS_HELPER_FUNCTION', 'sendSmsHelperFunction'),

];
```

## 4\. Usage

Use the package functions via the `LaraMultiAuth` facade:

```
use AhmedEbead\LaraMultiAuth\Facades\LaraMultiAuth;

// Login
$token = LaraMultiAuth::login([
    'email' => 'user@example.com',
    'password' => 'password123',
]);

// Register
$user = LaraMultiAuth::register([
    'email' => 'newuser@example.com',
    'password' => 'password123',
]);

// Generate OTP
$otp = LaraMultiAuth::generateOtp('1234567890');

// Verify OTP
$isVerified = LaraMultiAuth::verifyOtp('1234567890', $otp);

// Generate and send OTP
$otp = LaraMultiAuth::generateAndSendOtp('1234567890');
```

## 5\. Models Implementation

**BaseAuthModel**

The `AhmedEbead\LaraMultiAuth\Models\BaseAuthModel` provides common authentication functionalities.

**Concrete Models**

Extend `BaseAuthModel` for each guard.

**Web User Model**

```
<?php

namespace App\Models;

use AhmedEbead\LaraMultiAuth\Models\BaseAuthModel;

class WebUser extends BaseAuthModel
{
    protected $guardName = 'web';
}
```

**API User Model**

```
<?php

namespace App\Models;

use AhmedEbead\LaraMultiAuth\Models\BaseAuthModel;

class ApiUser extends BaseAuthModel
{
    protected $guardName = 'api';
}
```

**Admin User Model**

```
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

```
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

**Login with Email**

```
$token = LaraMultiAuth::login([
    'email' => 'user@example.com',
    'password' => 'password123',
]);
```

**Register a New User**

```
$user = LaraMultiAuth::register([
    'email' => 'newuser@example.com',
    'password' => 'password123',
]);
```

**Generate and Verify OTP**

```
$otp = LaraMultiAuth::generateOtp('1234567890');
$isVerified = LaraMultiAuth::verifyOtp('1234567890', $otp);
```

**Generate and Send OTP**

```
$otp = LaraMultiAuth::generateAndSendOtp('1234567890');
```

## 9\. License

The LaraMultiAuth package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more information.
