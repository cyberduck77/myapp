<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    |
    | Secret key used to verify JWT signatures. Use a dedicated key in production.
    |
    */
    'secret' => env('JWT_SECRET', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Signature Cookie Name
    |--------------------------------------------------------------------------
    |
    | When the token is split, the signature segment is sent in this cookie.
    |
    */
    'signature_cookie' => [
        'name' => env('JWT_SIGNATURE_COOKIE_NAME', 'at_sign'),
        'secure' => env('JWT_SIGNATURE_COOKIE_SECURE', false),
        'http_only' => env('JWT_SIGNATURE_COOKIE_HTTP_ONLY', true),
        'same_site' => env('JWT_SIGNATURE_COOKIE_SAME_SITE', 'lax'),
        'partitioned' => env('JWT_SIGNATURE_COOKIE_PARTITIONED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Cookie
    |--------------------------------------------------------------------------
    |
    | Refresh token is stored as an opaque random value in this HttpOnly cookie.
    |
    */
    'refresh_cookie' => [
        'lifetime' => env('JWT_REFRESH_COOKIE_LIFETIME', 60 * 24 * 7), //minutes
        'name' => env('JWT_REFRESH_COOKIE_NAME', 'rt'),
        'secure' => env('JWT_REFRESH_COOKIE_SECURE', false),
        'http_only' => env('JWT_REFRESH_COOKIE_HTTP_ONLY', true),
        'same_site' => env('JWT_REFRESH_COOKIE_SAME_SITE', 'lax'),
        'partitioned' => env('JWT_REFRESH_COOKIE_PARTITIONED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Leeway (seconds)
    |--------------------------------------------------------------------------
    |
    | Extra seconds of tolerance for exp/nbf claim validation (clock skew).
    |
    */
    'leeway' => (int) env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Allowed Algorithms
    |--------------------------------------------------------------------------
    |
    | Only these algorithms are accepted when decoding JWTs.
    |
    */
    'algorithms' => ['HS256'],

    /*
    |--------------------------------------------------------------------------
    | TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Token lifetime in seconds (e.g. 300 = 5 minutes).
    |
    */
    'ttl_seconds' => (int) env('JWT_TTL', 60 * 5),

    /*
    |--------------------------------------------------------------------------
    | Subject Claim
    |--------------------------------------------------------------------------
    |
    | Payload claim used to resolve the user (e.g. 'sub' for user id).
    |
    */
    'subject_claim' => env('JWT_SUBJECT_CLAIM', 'sub'),
];
