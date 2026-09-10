<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, OtpService $otpService): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'cnic' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d$/', 'unique:citizen_profiles,cnic'],
        ], [
            'cnic.regex' => 'CNIC must be in format 12345-1234567-1.',
            'cnic.unique' => 'This CNIC is already registered.',
        ]);

        $email = strtolower($data['email']);
        $payload = $otpService->issue($email, EmailOtp::PURPOSE_REGISTER);
        cache()->put('api_register:'.$email, [
            'name' => $data['name'],
            'cnic' => $data['cnic'],
        ], now()->addMinutes(30));

        return response()->json([
            'message' => 'OTP sent',
            'email' => $email,
            'expires_in_seconds' => $payload['expires_in_seconds'],
            'resend_in_seconds' => $payload['resend_in_seconds'],
            'debug_otp' => $payload['debug_otp'] ?? null,
        ]);
    }

    public function verifyOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower($data['email']);
        $cached = cache()->pull('api_register:'.$email);
        if (! $cached) {
            throw ValidationException::withMessages([
                'email' => 'No pending registration for this email. Start register again.',
            ]);
        }

        $name = is_array($cached) ? ($cached['name'] ?? null) : $cached;
        $cnic = is_array($cached) ? ($cached['cnic'] ?? null) : null;

        $otpService->verify($email, EmailOtp::PURPOSE_REGISTER, $data['otp']);

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $data['password'],
            'user_type' => User::TYPE_CITIZEN,
            'email_verified_at' => now(),
            'is_active' => true,
            'all_districts' => false,
        ]);
        $user->citizenProfile()->create([
            'full_name' => $name,
            'cnic' => $cnic,
        ]);

        return response()->json([
            'message' => 'Registered',
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', strtolower($credentials['email']))->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function forgotPassword(Request $request, OtpService $otpService): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);
        $email = strtolower($data['email']);
        if (! User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'No account found.']);
        }

        $payload = $otpService->issue($email, EmailOtp::PURPOSE_RESET);

        return response()->json([
            'message' => 'Reset OTP sent',
            'expires_in_seconds' => $payload['expires_in_seconds'],
            'debug_otp' => $payload['debug_otp'] ?? null,
        ]);
    }

    public function resetPassword(Request $request, OtpService $otpService): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower($data['email']);
        $otpService->verify($email, EmailOtp::PURPOSE_RESET, $data['otp']);
        $user = User::query()->where('email', $email)->firstOrFail();
        $user->password = $data['password'];
        $user->save();
        $user->tokens()->delete();

        return response()->json(['message' => 'Password updated']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user()->loadMissing('citizenProfile'))]);
    }

    public function verifyPattern(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pattern' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->hasPatternLock()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pattern lock is not enabled for this user.',
            ], 400);
        }

        if (! Hash::check($data['pattern'], $user->pattern_lock_hash)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid pattern lock sequence.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pattern verified successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'user_type' => $user->user_type,
            'profile_complete' => $user->hasCompleteCitizenProfile(),
            'pattern_lock_enabled' => (bool) $user->pattern_lock_enabled,
            'has_pattern_lock' => $user->hasPatternLock(),
        ];
    }
}
