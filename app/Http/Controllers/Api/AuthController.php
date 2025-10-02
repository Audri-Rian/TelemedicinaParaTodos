<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\Specialization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Doctor;

class AuthController extends Controller
{
    /**
     * Register a new user and authenticate automatically
     */
    public function register(Request $request): JsonResponse
    {
        // Log dos dados recebidos
        \Log::info('AuthController::register - Dados recebidos:', [
            'all_data' => $request->all(),
            'user_type' => $request->user_type,
            'crm' => $request->crm,
            'specializations' => $request->specializations,
            'specializations_type' => gettype($request->specializations),
            'headers' => $request->headers->all()
        ]);

        // Validação base
        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:doctor,patient',
        ];

        // Validação específica por tipo de usuário
        if ($request->user_type === 'doctor') {
            $baseRules['crm'] = 'required|string|max:20|unique:doctors,crm';
            // Temporariamente removendo validação de especializações para debug
            // $baseRules['specializations'] = 'required|array|min:1|max:5';
            // $baseRules['specializations.*'] = 'required|string|exists:specializations,id';
        } elseif ($request->user_type === 'patient') {
            $baseRules['date_of_birth'] = 'required|date|before:today';
            $baseRules['gender'] = 'required|in:male,female,other';
            $baseRules['phone_number'] = 'required|string|max:20';
        }

        $validator = Validator::make($request->all(), $baseRules);

        if ($validator->fails()) {
            \Log::error('AuthController::register - Erro de validação:', [
                'errors' => $validator->errors()->toArray(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Usar transação para garantir consistência
        try {
            $user = DB::transaction(function () use ($request) {
                // Create user
                \Log::info('AuthController::register - Criando usuário...');
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);
                \Log::info('AuthController::register - Usuário criado:', ['user_id' => $user->id]);

                // Create user profile based on type
                if ($request->user_type === 'doctor') {
                    \Log::info('AuthController::register - Criando perfil de médico:', [
                        'user_id' => $user->id,
                        'crm' => $request->crm,
                        'specializations' => $request->specializations ?? null
                    ]);
                    
                    $doctor = $user->doctor()->create([
                        'user_id' => $user->id,
                        'crm' => $request->crm,
                        'status' => 'active',
                    ]);
                    
                    // Attach specializations using N:N relationship
                    if ($request->specializations && is_array($request->specializations)) {
                        $doctor->specializations()->attach($request->specializations);
                        \Log::info('AuthController::register - Especializações vinculadas:', [
                            'doctor_id' => $doctor->id,
                            'specializations' => $request->specializations
                        ]);
                    }
                    
                    \Log::info('AuthController::register - Médico criado:', ['doctor_id' => $doctor->id]);
                } else {
                    \Log::info('AuthController::register - Criando perfil de paciente:', [
                        'user_id' => $user->id,
                        'date_of_birth' => $request->date_of_birth,
                        'gender' => $request->gender,
                        'phone_number' => $request->phone_number
                    ]);
                    
                    $patient = $user->patient()->create([
                        'user_id' => $user->id,
                        'date_of_birth' => $request->date_of_birth ?? null,
                        'gender' => $request->gender ?? null,
                        'phone_number' => $request->phone_number ?? null,
                    ]);
                    
                    \Log::info('AuthController::register - Paciente criado:', ['patient_id' => $patient->id]);
                }

                return $user;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Capture errors específicos do banco de dados
            \Log::error('AuthController::register - Erro de banco de dados:', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'data' => $request->all()
            ]);
            
            // Se for erro de CRM duplicado, retornar erro específico
            if (str_contains($e->getMessage(), 'doctors_crm_unique') || str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => [
                        'crm' => ['Este CRM já está cadastrado. Use outro CRM.']
                    ]
                ], 422);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error' => 'Erro interno do servidor. Tente novamente.'
            ], 500);
        } catch (\Exception $e) {
            \Log::error('AuthController::register - Erro geral:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Registration error',
                'error' => 'Erro interno do servidor. Tente novamente.'
            ], 500);
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

        // Check if user is active (only if status field exists)
        if (isset($user->status) && $user->status !== 'active') {
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

    /**
     * Get specializations (temporary for debugging)
     */
    public function specializations(): JsonResponse
    {
        $specializations = Specialization::all(['id', 'name']);
        
        \Log::info('AuthController::specializations - Especializações encontradas:', [
            'count' => $specializations->count(),
            'specializations' => $specializations->toArray()
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'specializations' => $specializations
            ]
        ]);
    }
}