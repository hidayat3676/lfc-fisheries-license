<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        $staffUsers = User::query()
            ->where('is_active', true)
            ->whereIn('user_type', [User::TYPE_SUPER_ADMIN, User::TYPE_ADMIN, User::TYPE_EXECUTIVE])
            ->with('adminProfile:id,user_id,designation_label')
            ->orderBy('name')
            ->get(['id', 'name', 'user_type']);

        return view('auth.register', compact('staffUsers'));
    }

    public function sendOtp(Request $request, OtpService $otpService): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'cnic' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d$/', 'unique:citizen_profiles,cnic'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profiles', 'public');
        }

        // Direct registration (no OTP required when password is provided or email is omitted)
        if (! empty($data['password']) || $email === null) {
            if (empty($data['password'])) {
                $request->validate([
                    'password' => ['required', 'string', 'min:8', 'confirmed'],
                ]);
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
                'photo_path' => $photoPath,
            ]);

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('citizen.dashboard')->with('status', 'Account created successfully.');
        }

        $payload = $otpService->issue($email, EmailOtp::PURPOSE_REGISTER);

        $request->session()->put('register', [
            'name' => $data['name'],
            'email' => $email,
            'cnic' => $data['cnic'],
            'photo_path' => $photoPath,
            'referred_by' => $data['referred_by'] ?? null,
            'expires_at' => $payload['expires_at'],
            'resend_in_seconds' => $payload['resend_in_seconds'],
        ]);

        if (isset($payload['debug_otp'])) {
            $request->session()->flash('debug_otp', $payload['debug_otp']);
        }

        return redirect()
            ->route('register.verify')
            ->with('status', 'Verification code sent to your email (OTP is optional).');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('register')) {
            return redirect()->route('register');
        }

        return view('auth.verify-otp', [
            'mode' => 'register',
            'email' => $request->session()->get('register.email'),
            'expiresAt' => $request->session()->get('register.expires_at'),
            'resendIn' => $request->session()->get('register.resend_in_seconds', 60),
        ]);
    }

    public function verify(Request $request, OtpService $otpService): RedirectResponse
    {
        $session = $request->session()->get('register');
        if (! $session) {
            return redirect()->route('register');
        }

        $data = $request->validate([
            'otp' => ['nullable', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! empty($data['otp']) && ! empty($session['email'])) {
            $otpService->verify($session['email'], EmailOtp::PURPOSE_REGISTER, $data['otp']);
        }

        $user = User::query()->create([
            'name' => $session['name'],
            'email' => $session['email'] ?? null,
            'password' => $data['password'],
            'user_type' => User::TYPE_CITIZEN,
            'referred_by' => $session['referred_by'] ?? null,
            'email_verified_at' => now(),
            'is_active' => true,
            'all_districts' => false,
        ]);

        $user->citizenProfile()->create([
            'full_name' => $session['name'],
            'cnic' => $session['cnic'] ?? null,
            'photo_path' => $session['photo_path'] ?? null,
        ]);

        $request->session()->forget('register');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('citizen.dashboard')->with('status', 'Account created successfully.');
    }

    public function resend(Request $request, OtpService $otpService): RedirectResponse
    {
        $session = $request->session()->get('register');
        if (! $session || empty($session['email'])) {
            return redirect()->route('register');
        }

        $payload = $otpService->issue($session['email'], EmailOtp::PURPOSE_REGISTER);
        $session['expires_at'] = $payload['expires_at'];
        $session['resend_in_seconds'] = $payload['resend_in_seconds'];
        $request->session()->put('register', $session);

        if (isset($payload['debug_otp'])) {
            $request->session()->flash('debug_otp', $payload['debug_otp']);
        }

        return back()->with('status', 'A new verification code was sent.');
    }
}
