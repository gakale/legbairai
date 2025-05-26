<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paystack Keys
    |--------------------------------------------------------------------------
    |
    | The Paystack publishable key and secret key give you access to Paystack's
    | API. The "publishable" key is typically used when interacting with
    | Paystack.js while the "secret" key accesses private API endpoints.
    |
    */

    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Paystack API URL
    |--------------------------------------------------------------------------
    |
    | This is the base URL for Paystack API requests. By default this will use
    | the Paystack production environment, but you may override this when needed.
    |
    */

    'payment_url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),

    /*
    |--------------------------------------------------------------------------
    | Paystack Currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency that will be used when generating charges
    | from your application. Of course, you are welcome to use any of the
    | various currencies that Paystack supports in your application.
    |
    */

    'currency' => env('PAYSTACK_CURRENCY', 'XOF'),
];   