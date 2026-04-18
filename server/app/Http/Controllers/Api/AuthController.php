<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Firebase\JWT\JWT;
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

        return response()->json([
            'at' => $token
        ]);
    }
}
