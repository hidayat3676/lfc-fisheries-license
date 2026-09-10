<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function sendOtp(Request $request, OtpService $otpService): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'cnic' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d$/', 'unique:citizen_profiles,cnic'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'cnic.regex' => 'CNIC must be in format 12345-1234567-1.',
            'cnic.unique' => 'This CNIC is already registered.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profiles', 'public');
        }

        $payload = $otpService->issue($data['email'], EmailOtp::PURPOSE_REGISTER);

        $request->session()->put('register', [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'cnic' => $data['cnic'],
            'photo_path' => $photoPath,
            'expires_at' => $payload['expires_at'],
            'resend_in_seconds' => $payload['resend_in_seconds'],
        ]);

        if (isset($payload['debug_otp'])) {
            $request->session()->flash('debug_otp', $payload['debug_otp']);
        }

        return redirect()
            ->route('register.verify')
            ->with('status', 'Verification code sent to your email.');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('register.email')) {
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
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $otpService->verify($session['email'], EmailOtp::PURPOSE_REGISTER, $data['otp']);

        $user = User::query()->create([
            'name' => $session['name'],
            'email' => $session['email'],
            'password' => $data['password'],
            'user_type' => User::TYPE_CITIZEN,
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
        if (! $session) {
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
