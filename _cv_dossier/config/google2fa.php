<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Two Factor Authentication Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Google Authenticator 2FA.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Issuer (App Name)
    |--------------------------------------------------------------------------
    |
    | The name that will appear in the user's authenticator app.
    |
    */
    'issuer' => env('APP_NAME', 'AQAR'),

    /*
    |--------------------------------------------------------------------------
    | Secret Key Length
    |--------------------------------------------------------------------------
    |
    | The length of the secret key generated for each user.
    | Default is 16 characters which provides good security.
    |
    */
    'secret_length' => 16,

    /*
    |--------------------------------------------------------------------------
    | Code Window
    |--------------------------------------------------------------------------
    |
    | The number of 30-second windows before and after the current time
    | that are considered valid. A window of 1 means codes from 30 seconds
    | before and after are valid (total 90 seconds).
    |
    */
    'window' => 1,

    /*
    |--------------------------------------------------------------------------
    | QR Code Size
    |--------------------------------------------------------------------------
    |
    | The size of the generated QR code in pixels.
    |
    */
    'qr_code_size' => 200,

    /*
    |--------------------------------------------------------------------------
    | Trusted Device Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the "Remember this device" feature.
    |
    */
    'trusted_device' => [
        /*
        |--------------------------------------------------------------------------
        | Expiration (Days)
        |--------------------------------------------------------------------------
        |
        | How many days a trusted device remains valid.
        |
        */
        'expiration_days' => env('TWO_FACTOR_TRUSTED_DEVICE_DAYS', 30),

        /*
        |--------------------------------------------------------------------------
        | Maximum Devices
        |--------------------------------------------------------------------------
        |
        | Maximum number of trusted devices per user.
        | Set to null for unlimited.
        |
        */
        'max_devices' => env('TWO_FACTOR_MAX_DEVICES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Settings for rate limiting 2FA verification attempts.
    |
    */
    'rate_limit' => [
        /*
        |--------------------------------------------------------------------------
        | Maximum Attempts
        |--------------------------------------------------------------------------
        |
        | Maximum number of failed verification attempts before lockout.
        |
        */
        'max_attempts' => env('TWO_FACTOR_MAX_ATTEMPTS', 5),

        /*
        |--------------------------------------------------------------------------
        | Lockout Duration (Minutes)
        |--------------------------------------------------------------------------
        |
        | How long a user is locked out after exceeding max attempts.
        |
        */
        'lockout_minutes' => env('TWO_FACTOR_LOCKOUT_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Token Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the temporary token issued during 2FA login flow.
    |
    */
    'token' => [
        /*
        |--------------------------------------------------------------------------
        | Expiration (Minutes)
        |--------------------------------------------------------------------------
        |
        | How long the 2FA token remains valid after password authentication.
        |
        */
        'expiration_minutes' => env('TWO_FACTOR_TOKEN_EXPIRATION', 5),
    ],

];
