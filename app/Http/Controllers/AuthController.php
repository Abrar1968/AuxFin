<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasskeyRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = User::query()->with('employee')->where('email', $payload['email'])->first();

        if (! $user || ! Hash::check($payload['passkey'], $user->passkey)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account is inactive.'], 403);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $abilities = match ($user->role) {
            'super_admin', 'admin' => ['admin'],
            default => ['employee'],
        };

        $tokenExpirationMinutes = max(1, (int) config('sanctum.expiration', 480));
        $token = $user->createToken(
            $user->createTokenName(),
            $abilities,
            now()->addMinutes($tokenExpirationMinutes)
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'employee_code' => $user->employee?->employee_code,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $tokenId = $user->currentAccessToken()?->id;
            if ($tokenId) {
                $user->tokens()->whereKey($tokenId)->delete();
            }
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function changePasskey(ChangePasskeyRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = $request->user();

        if (! Hash::check($payload['current_passkey'], $user->passkey)) {
            return response()->json(['message' => 'Current passkey is incorrect.'], 422);
        }

        $user->update([
            'passkey' => $payload['new_passkey'],
            'passkey_plain' => null,
        ]);

        return response()->json(['message' => 'Passkey changed successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('employee'),
        ]);
    }
}
