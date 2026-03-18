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
    'signature_cookie' => env('JWT_SIGNATURE_COOKIE', 'jwt_signature'),

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
    | Token lifetime in seconds (e.g. 3600 = 1 hour).
    |
    */
    'ttl_seconds' => (int) env('JWT_TTL', 3600),

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
