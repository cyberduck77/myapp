<?php

namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use stdClass;

class JwtGuard implements Guard
{
    use \Illuminate\Auth\GuardHelpers;

    public function __construct(
        UserProvider $provider,
        protected Request $request,
        protected array $config
    ) {
        $this->provider = $provider;
    }

    /**
     * Set the current request instance (used when request is refreshed).
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $tokenString = $this->getTokenString();
        if ($tokenString === null) {
            return null;
        }

        $payload = $this->decodeAndVerify($tokenString);
        if ($payload === null) {
            return null;
        }

        $subjectClaim = $this->config['subject_claim'] ?? 'sub';
        $sub = $payload->$subjectClaim ?? null;
        if ($sub === null) {
            return null;
        }

        $this->user = $this->provider->retrieveById($sub);

        return $this->user;
    }

    /**
     * Reconstruct Bearer token: from full JWT or header.payload + cookie signature.
     */
    protected function getTokenString(): ?string
    {
        $bearer = $this->request->bearerToken();
        if ($bearer === null || $bearer === '') {
            return null;
        }

        $parts = explode('.', $bearer);

        if (count($parts) === 3) {
            return $bearer;
        }

        if (count($parts) === 2) {
            $cookieName = $this->config['cookie_name'] ?? 'jwt_signature';
            $signature = $this->request->cookie($cookieName);
            if ($signature === null || $signature === '') {
                return null;
            }
            return $parts[0] . '.' . $parts[1] . '.' . $signature;
        }

        return null;
    }

    /**
     * Decode and verify JWT; return payload or null on failure.
     */
    protected function decodeAndVerify(string $jwt): ?stdClass
    {
        $secret = $this->config['secret'] ?? '';
        if ($secret === '') {
            return null;
        }

        $algorithms = $this->config['algorithms'] ?? ['HS256'];
        $alg = $algorithms[0];

        try {
            $key = new Key($secret, $alg);
            $payload = JWT::decode($jwt, $key);
            return $payload;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Validate a user's credentials (for stateless guard: resolve user from request token).
     */
    public function validate(array $credentials = []): bool
    {
        return $this->user() !== null;
    }
}
