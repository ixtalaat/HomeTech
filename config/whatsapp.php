<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "log" (development: writes to the log, code visible),
    | "twilio" (Twilio WhatsApp API), "meta" (WhatsApp Cloud API).
    |
    */

    'driver' => env('WHATSAPP_DRIVER', 'log'),

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_WHATSAPP_FROM'),
    ],

    'meta' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Codes
    |--------------------------------------------------------------------------
    */

    'code_ttl_minutes' => 10,
    'code_max_attempts' => 5,
];
