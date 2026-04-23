<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Login: validate credentials and return a JWT.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $secret = config('jwt.secret');
        $alg = config('jwt.algorithms')[0] ?? 'HS256';
        $ttl = (int) config('jwt.ttl_seconds', 3600);

        $payload = [
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + $ttl,
            'name' => $user->name,
        ];

        $token = JWT::encode($payload, $secret, $alg);
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw ValidationException::withMessages([
                'email' => ['Unable to issue access token.'],
            ]);
        }

        [$header, $body, $signature] = $parts;
        $signatureCookieConfig = config('jwt.signature_cookie');
        $minutes = max(1, (int) ceil($ttl / 60));
        $signatureCookie = Cookie::make(
            $signatureCookieConfig['name'],
            $signature,
            $minutes,
            '/',
            null,
            $signatureCookieConfig['secure'],
            $signatureCookieConfig['http_only'],
            $signatureCookieConfig['same_site'],
            $signatureCookieConfig['partitioned']
        );

        return response()->json([
            'at' => $header . '.' . $body,
        ])->cookie($signatureCookie);
    }
}
