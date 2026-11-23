<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function staffUsers(): JsonResponse
    {
        $staffUsers = User::query()
            ->where('is_active', true)
            ->whereIn('user_type', [User::TYPE_SUPER_ADMIN, User::TYPE_ADMIN, User::TYPE_EXECUTIVE])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
            ]);

        return response()->json([
            'data' => $staffUsers,
        ]);
    }

    public function register(Request $request, OtpService $otpService): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'cnic' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d$/', 'unique:citizen_profiles,cnic'],
            'otp' => ['nullable', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'referred_by' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->whereIn('user_type', [
                    User::TYPE_SUPER_ADMIN,
                    User::TYPE_ADMIN,
                    User::TYPE_EXECUTIVE,
                ]),
            ],

        ], [
            'cnic.regex' => 'CNIC must be in format 12345-1234567-1.',
            'cnic.unique' => 'This CNIC is already registered.',
        ]);

        $email = ! empty($data['email']) ? strtolower(trim($data['email'])) : null;

        // Direct registration without OTP when password is provided or email is omitted
        if (! empty($data['password']) || $email === null) {
            if (empty($data['password'])) {
                throw ValidationException::withMessages([
                    'password' => 'Password is required when registering without email OTP.',
                ]);
            }

            if (! empty($data['otp']) && $email !== null) {
                $otpService->verify($email, EmailOtp::PURPOSE_REGISTER, $data['otp']);
            }

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'password' => $data['password'],
                'user_type' => User::TYPE_CITIZEN,
                'referred_by' => $data['referred_by'] ?? null,
                'email_verified_at' => now(),
                'is_active' => true,
                'all_districts' => false,
            ]);
            $user->citizenProfile()->create([
                'full_name' => $data['name'],
                'cnic' => $data['cnic'],
            ]);

            return response()->json([
                'message' => 'Registered',
                'token' => $user->createToken('mobile')->plainTextToken,
                'user' => $this->userPayload($user),
            ], 201);
        }

        $payload = $otpService->issue($email, EmailOtp::PURPOSE_REGISTER);
        cache()->put('api_register:'.$email, [
            'name' => $data['name'],
            'cnic' => $data['cnic'],
            'referred_by' => $data['referred_by'] ?? null,
        ], now()->addMinutes(30));

        return response()->json([
            'message' => 'OTP sent (optional)',
            'email' => $email,
            'expires_in_seconds' => $payload['expires_in_seconds'],
            'resend_in_seconds' => $payload['resend_in_seconds'],
            'debug_otp' => $payload['debug_otp'] ?? null,
        ]);
    }

    public function verifyOtp(Request $request, OtpService $otpService): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email'],
            'cnic' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:120'],
            'otp' => ['nullable', 'string', 'size:6'],
            'referred_by' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->whereIn('user_type', [
                    User::TYPE_SUPER_ADMIN,
                    User::TYPE_ADMIN,
                    User::TYPE_EXECUTIVE,
                ]),
            ],
        ]);

        $email = ! empty($data['email']) ? strtolower(trim($data['email'])) : null;
        $cached = $email ? cache()->pull('api_register:'.$email) : null;

        if ($email && ! $cached && empty($data['cnic'])) {
            throw ValidationException::withMessages([
                'email' => 'No pending registration for this email. Start register again.',
            ]);
        }

        $name = is_array($cached) ? ($cached['name'] ?? null) : ($data['name'] ?? $cached);
        $cnic = is_array($cached) ? ($cached['cnic'] ?? null) : ($data['cnic'] ?? null);
        $referredBy = is_array($cached) ? ($cached['referred_by'] ?? ($data['referred_by'] ?? null)) : ($data['referred_by'] ?? null);

        if (! empty($data['otp']) && $email) {
            $otpService->verify($email, EmailOtp::PURPOSE_REGISTER, $data['otp']);
        }

        $user = User::query()->create([
            'name' => $name ?: 'Citizen',
            'email' => $email,
            'user_type' => User::TYPE_CITIZEN,
            'referred_by' => $referredBy,
            'email_verified_at' => now(),
            'is_active' => true,
            'all_districts' => false,
        ]);
        $user->citizenProfile()->create([
            'full_name' => $name ?: 'Citizen',
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
            'email' => ['nullable', 'string'],
            'cnic' => ['required', 'string'],
            'login' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim((string) ($credentials['login'] ?? 
        $credentials['cnic'] ?? 
        $credentials['email'] ?? ''));
        if ($identifier === '') {
            throw ValidationException::withMessages([
                'cnic' => 'Please provide CNIC or email.',
            ]);
        }

        if (str_contains($identifier, '@')) {
            $user = User::query()->where('email', strtolower($identifier))->first();
        } else {
            $digits = preg_replace('/\D/', '', $identifier);
            $user = User::query()
                ->whereHas('citizenProfile', function ($q) use ($identifier, $digits) {
                    $q->where('cnic', $identifier);
                    if ($digits !== '') {
                        $q->orWhereRaw("REPLACE(cnic, '-', '') = ?", [$digits]);
                    }
                })
                ->first();
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'cnic' => 'Invalid credentials.',
            ]);
        }

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $this->userPayload($user->loadMissing('citizenProfile')),
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
        $user->loadMissing(['citizenProfile', 'referredBy:id,name']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'cnic' => $user->citizenProfile?->cnic,
            'mobile' => $user->mobile,
            'user_type' => $user->user_type,
            'referred_by' => $user->referred_by,
            'referred_by_name' => $user->referredBy?->name,
            'profile_complete' => $user->hasCompleteCitizenProfile(),
            'pattern_lock_enabled' => (bool) $user->pattern_lock_enabled,
            'has_pattern_lock' => $user->hasPatternLock(),
        ];
    }
}
