<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request, OtpService $otpService): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($data['email']);
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'No account found for this email.',
            ]);
        }

        $payload = $otpService->issue($email, EmailOtp::PURPOSE_RESET);

        $request->session()->put('password_reset', [
            'email' => $email,
            'expires_at' => $payload['expires_at'],
            'resend_in_seconds' => $payload['resend_in_seconds'],
        ]);

        if (isset($payload['debug_otp'])) {
            $request->session()->flash('debug_otp', $payload['debug_otp']);
        }

        return redirect()
            ->route('password.reset')
            ->with('status', 'Password reset code sent to your email.');
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset.email')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', [
            'email' => $request->session()->get('password_reset.email'),
            'expiresAt' => $request->session()->get('password_reset.expires_at'),
            'resendIn' => $request->session()->get('password_reset.resend_in_seconds', 60),
        ]);
    }

    public function reset(Request $request, OtpService $otpService): RedirectResponse
    {
        $session = $request->session()->get('password_reset');
        if (! $session) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $otpService->verify($session['email'], EmailOtp::PURPOSE_RESET, $data['otp']);

        $user = User::query()->where('email', $session['email'])->firstOrFail();
        $user->password = $data['password'];
        $user->save();

        $request->session()->forget('password_reset');

        $loginRoute = $user->isAdminStaff() ? 'staff.login' : 'login';

        return redirect()->route($loginRoute)->with('status', 'Password updated. You can sign in now.');
    }

    public function resend(Request $request, OtpService $otpService): RedirectResponse
    {
        $session = $request->session()->get('password_reset');
        if (! $session) {
            return redirect()->route('password.request');
        }

        $payload = $otpService->issue($session['email'], EmailOtp::PURPOSE_RESET);
        $session['expires_at'] = $payload['expires_at'];
        $session['resend_in_seconds'] = $payload['resend_in_seconds'];
        $request->session()->put('password_reset', $session);

        if (isset($payload['debug_otp'])) {
            $request->session()->flash('debug_otp', $payload['debug_otp']);
        }

        return back()->with('status', 'A new reset code was sent.');
    }
}
