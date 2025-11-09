<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;
    
     // Register a new user.
     
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->createdResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ], 'User registered successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Registration failed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred during registration');
        }
    }

    
     // Login user and create token.
     
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->unauthorizedResponse('The provided credentials are incorrect');
            }

            

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ], 'Login successful');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Login failed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred during login');
        }
    }

    
     // Logout user (Revoke the token).
     
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse(null, 'Logged out successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred during logout');
        }
    }

    
     // Get authenticated user details.
     
    public function me(Request $request): JsonResponse
    {
        try {
            return $this->successResponse([
                'user' => $request->user(),
            ], 'User details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while retrieving user details');
        }
    }
}