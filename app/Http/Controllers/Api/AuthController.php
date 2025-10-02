<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and authenticate automatically
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:doctor,patient',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create user profile based on type
        if ($request->user_type === 'doctor') {
            $user->doctor()->create([
                'user_id' => $user->id,
                'crm' => $request->crm ?? null,
                'specialization_id' => $request->specialization_id ?? null,
            ]);
        } else {
            $user->patient()->create([
                'user_id' => $user->id,
                'birth_date' => $request->birth_date ?? null,
                'gender' => $request->gender ?? null,
                'phone' => $request->phone ?? null,
            ]);
        }

        // Generate token
        $token = $user->createToken('auth_token', ['*'], now()->addHours(24))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered and authenticated successfully',
            'data' => [
                'user' => $user->load(['doctor', 'patient']),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addHours(24)->toISOString(),
                'redirect_to' => $request->user_type === 'doctor' ? '/doctor/dashboard' : '/patient/dashboard'
            ]
        ], 201);
    }

    /**
     * Login user and generate token
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check credentials
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        // Check if user is active
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active'
            ], 403);
        }

        // Revoke existing tokens
        $user->tokens()->delete();

        // Generate new token
        $token = $user->createToken('auth_token', ['*'], now()->addHours(24))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->load(['doctor', 'patient']),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addHours(24)->toISOString(),
                'redirect_to' => $user->getRole() === 'doctor' ? '/doctor/dashboard' : '/patient/dashboard'
            ]
        ]);
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()->load(['doctor', 'patient'])
            ]
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Revoke current token
        $request->user()->currentAccessToken()->delete();
        
        // Generate new token
        $token = $user->createToken('auth_token', ['*'], now()->addHours(24))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addHours(24)->toISOString()
            ]
        ]);
    }
}