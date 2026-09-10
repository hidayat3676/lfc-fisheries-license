<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OtpService
{
    public function issue(string $email, string $purpose): array
    {
        $email = strtolower(trim($email));
        $ttl = (int) config('rflms.otp.ttl_minutes', 10);
        $cooldown = (int) config('rflms.otp.resend_cooldown_seconds', 60);
        $length = (int) config('rflms.otp.length', 6);

        $latest = EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($latest && $latest->last_sent_at) {
            $wait = $cooldown - $latest->last_sent_at->diffInSeconds(now());
            if ($wait > 0) {
                throw ValidationException::withMessages([
                    'email' => "Please wait {$wait} seconds before requesting another code.",
                ]);
            }
        }

        EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode($length);

        $otp = EmailOtp::query()->create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        Mail::to($email)->send(new OtpMail($code, $purpose, $ttl));

        $payload = [
            'email' => $email,
            'purpose' => $purpose,
            'expires_at' => $otp->expires_at->toIso8601String(),
            'expires_in_seconds' => $ttl * 60,
            'resend_in_seconds' => $cooldown,
        ];

        if (config('app.debug')) {
            $payload['debug_otp'] = $code;
        }

        return $payload;
    }

    public function verify(string $email, string $purpose, string $code): EmailOtp
    {
        $email = strtolower(trim($email));
        $maxAttempts = (int) config('rflms.otp.max_attempts', 5);

        $otp = EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp' => 'No active verification code found. Please request a new one.',
            ]);
        }

        if ($otp->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => 'This code has expired. Please request a new one.',
            ]);
        }

        if ($otp->attempts >= $maxAttempts) {
            throw ValidationException::withMessages([
                'otp' => 'Too many invalid attempts. Please request a new code.',
            ]);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw ValidationException::withMessages([
                'otp' => 'Invalid verification code.',
            ]);
        }

        $otp->update(['consumed_at' => now()]);

        return $otp;
    }

    private function generateCode(int $length): string
    {
        if ($length < 4 || $length > 8) {
            throw new RuntimeException('OTP length must be between 4 and 8.');
        }

        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}
