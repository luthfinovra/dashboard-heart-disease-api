<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthUserService
{
    public function register(
        string $name,
        string $email,
        string $password,
        ?string $institution = null,
        ?string $gender = null,
        ?string $phoneNumber = null,
        ?string $tujuanPermohonan = null
    ): array {
        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'institution' => $institution,
                'gender' => $gender,
                'phone_number' => $phoneNumber,
                'tujuan_permohonan' => $tujuanPermohonan,
                'role' => 'peneliti',
                'approval_status' => 'pending',
            ]);

            return [true, 'User registered successfully.', [
                'approval_status' => $user->approval_status,
                ]];
        } catch (\Throwable $exception) {
            // TO DO logging
            return [false, 'Registration failed: ', []];
        }
    }

    public function login(string $email, string $password): array
    {
        try {
            $user = User::where('email', $email)->first();
    
            if (!$user || !Hash::check($password, $user->password)) {
                return [false, 'The provided credentials are incorrect.', []];
            }
    
            $token = $user->createToken('MyApp')->plainTextToken;
    
            $response = [
                'token' => $token,
                'approval_status' => $user->approval_status,
                'role' => $user->role,
            ];
    
            if ($user->role === 'operator') {
                $response['disease_id'] = $user->managedDiseases["disease_id"] ?? null;
            }
    
            return [true, 'Login successful.', $response];
        } catch (\Throwable $exception) {
            // TO DO logging
            return [false, 'Login Failed: ' . $exception->getMessage(), []];
        }
    }
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    private function deleteTokenSanctum(int $userId): void
    {
        \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $userId)
            ->where('abilities', '["accessLoginMember"]')
            ->delete();
    }
}
