<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

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

        [$accessToken, $signatureCookie] = $this->issueAccessToken($user);
        [$refreshToken, $refreshCookie, $refreshExpiresAt] = $this->issueRefreshToken();

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $refreshToken),
            'expires_at' => $refreshExpiresAt,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'at' => $accessToken,
        ])
            ->cookie($signatureCookie)
            ->cookie($refreshCookie);
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshCookieName = config('jwt.refresh_cookie.name', 'rt');
        $refreshToken = $request->cookie($refreshCookieName);
        if (!is_string($refreshToken) || $refreshToken === '') {
            return $this->unauthorizedWithClearedCookies('Refresh token missing.');
        }

        $storedToken = RefreshToken::where('token_hash', hash('sha256', $refreshToken))
            ->whereNull('revoked_at')
            ->first();

        if ($storedToken === null || $storedToken->expires_at->isPast()) {
            if ($storedToken !== null) {
                $storedToken->update(['revoked_at' => now()]);
            }
            return $this->unauthorizedWithClearedCookies('Refresh token is invalid or expired.');
        }

        $user = User::find($storedToken->user_id);
        if ($user === null) {
            $storedToken->update(['revoked_at' => now()]);
            return $this->unauthorizedWithClearedCookies('User no longer exists.');
        }

        $storedToken->update(['revoked_at' => now()]);

        [$accessToken, $signatureCookie] = $this->issueAccessToken($user);
        [$nextRefreshToken, $refreshCookie, $nextRefreshExpiresAt] = $this->issueRefreshToken();

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $nextRefreshToken),
            'expires_at' => $nextRefreshExpiresAt,
            'rotated_from_id' => $storedToken->id,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'at' => $accessToken,
        ])
            ->cookie($signatureCookie)
            ->cookie($refreshCookie);
    }

    public function logout(Request $request): JsonResponse
    {
        $refreshCookieName = config('jwt.refresh_cookie.name', 'rt');
        $refreshToken = $request->cookie($refreshCookieName);
        if (is_string($refreshToken) && $refreshToken !== '') {
            RefreshToken::where('token_hash', hash('sha256', $refreshToken))
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }

        return response()->json([
            'message' => 'Logged out.',
        ])->withCookie($this->forgetSignatureCookie())
            ->withCookie($this->forgetRefreshCookie());
    }

    /**
     * @return array{0: string, 1: \Symfony\Component\HttpFoundation\Cookie}
     */
    protected function issueAccessToken(User $user): array
    {
        $secret = config('jwt.secret');
        $alg = config('jwt.algorithms')[0] ?? 'HS256';
        $ttl = (int) config('jwt.ttl_seconds', 3600);

        $payload = [
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + $ttl,
            'name' => $user->name,
            'email' => $user->email,
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
            (bool) $signatureCookieConfig['secure'],
            (bool) $signatureCookieConfig['http_only'],
            false,
            $signatureCookieConfig['same_site'] ?? 'lax'
        );

        return [$header . '.' . $body, $signatureCookie];
    }

    /**
     * @return array{0: string, 1: \Symfony\Component\HttpFoundation\Cookie, 2: \Illuminate\Support\Carbon}
     */
    protected function issueRefreshToken(): array
    {
        $refreshToken = Str::random(96);
        $refreshCookieConfig = config('jwt.refresh_cookie');
        $refreshCookie = Cookie::make(
            $refreshCookieConfig['name'],
            $refreshToken,
            $refreshCookieConfig['lifetime'],
            '/',
            null,
            (bool) $refreshCookieConfig['secure'],
            (bool) $refreshCookieConfig['http_only'],
            false,
            $refreshCookieConfig['same_site'] ?? 'lax'
        );
        $expiresAt = now()->addMinutes($refreshCookieConfig['lifetime']);

        return [$refreshToken, $refreshCookie, $expiresAt];
    }

    protected function unauthorizedWithClearedCookies(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401)->withCookie($this->forgetSignatureCookie())
            ->withCookie($this->forgetRefreshCookie());
    }

    protected function forgetSignatureCookie(): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::forget(config('jwt.signature_cookie.name', 'at_sign'));
    }

    protected function forgetRefreshCookie(): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::forget(config('jwt.refresh_cookie.name', 'rt'));
    }
}
