<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to register user', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);

            return response()->json([
                'message' => 'Failed to register user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during registration',
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            if (! Auth::attempt($request->only('email', 'password'))) {
                Log::warning('Failed login attempt', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to login user', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);

            return response()->json([
                'message' => 'Failed to login',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during login',
            ], 500);
        }
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logged out successfully',
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to logout user', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Failed to logout',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during logout',
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at->toIso8601String(),
                    ],
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to fetch user profile', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Failed to fetch user profile',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }
}
