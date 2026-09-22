<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Service Account Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service-account JSON. The file itself is
    | git-ignored and must exist on every machine that sends push
    | notifications. Sending is skipped silently when it is missing.
    |
    */
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/firebase-credentials.json')),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Overrides the project_id read from the credentials file above.
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Web Push VAPID Key
    |--------------------------------------------------------------------------
    |
    | Public key from Firebase Console > Project settings > Cloud Messaging
    | > Web Push certificates. Required for browser push subscriptions.
    |
    */
    'vapid_key' => env('FIREBASE_VAPID_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Public Web Configuration
    |--------------------------------------------------------------------------
    |
    | Client identifiers from Firebase Console > Project settings > Your apps
    | (web app). These are public by design and served to browsers so the
    | Firebase JS SDK can subscribe this device for push messages.
    |
    */
    'web' => [
        'api_key' => env('FIREBASE_WEB_API_KEY'),
        'auth_domain' => env('FIREBASE_WEB_AUTH_DOMAIN'),
        'project_id' => env('FIREBASE_WEB_PROJECT_ID'),
        'sender_id' => env('FIREBASE_WEB_SENDER_ID'),
        'app_id' => env('FIREBASE_WEB_APP_ID'),
    ],
];
