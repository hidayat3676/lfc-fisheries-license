<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BrevoMailService
{
    public function isConfigured(): bool
    {
        return trim((string) config('services.brevo.key', '')) !== '';
    }

    public function sendEmail(string $toEmail, string $subject, string $htmlContent, ?string $textContent = null): bool
    {
        $apiKey = trim((string) config('services.brevo.key', ''));
        if ($apiKey === '') {
            return false;
        }

        $senderEmail = (string) config('services.brevo.sender_email', config('mail.from.address', 'noreply@fisheries.kp.gov.pk'));
        $senderName = (string) config('services.brevo.sender_name', config('mail.from.name', config('app.name', 'KP Fisheries E-License')));
        $endpoint = (string) config('services.brevo.endpoint', 'https://api.brevo.com/v3/smtp/email');

        $payload = [
            'sender' => [
                'name' => $senderName,
                'email' => $senderEmail,
            ],
            'to' => [
                ['email' => $toEmail],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ];

        if ($textContent !== null && $textContent !== '') {
            $payload['textContent'] = $textContent;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'api-key' => $apiKey,
                    'accept' => 'application/json',
                ])
                ->asJson()
                ->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info('Brevo transactional email sent.', [
                    'to' => $toEmail,
                    'subject' => $subject,
                    'message_id' => $response->json('messageId'),
                ]);

                return true;
            }

            Log::warning('Brevo transactional email failed.', [
                'to' => $toEmail,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (Throwable $e) {
            Log::warning('Brevo transactional email exception: '.$e->getMessage(), [
                'to' => $toEmail,
            ]);

            return false;
        }
    }

    public function sendOtpEmail(string $toEmail, string $code, string $purpose, int $ttlMinutes): bool
    {
        $subject = match ($purpose) {
            'register' => 'Your registration verification code',
            'reset' => 'Your password reset code',
            'login' => 'Your login verification code',
            default => 'Your verification code',
        };
        $fullSubject = $subject.' — '.config('app.name');

        $actionText = $purpose === 'reset'
            ? 'reset your password'
            : ($purpose === 'register' ? 'complete your registration' : 'verify your account');

        $html = sprintf(
            '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:520px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;">'
            .'<h2 style="margin:0 0 12px;color:#0b6e4f;">%s</h2>'
            .'<p style="margin:0 0 16px;color:#334155;">Use the verification code below to %s:</p>'
            .'<div style="font-size:28px;font-weight:700;letter-spacing:6px;padding:14px 20px;background:#f1f5f9;border-radius:8px;text-align:center;color:#0f172a;margin:0 0 16px;">%s</div>'
            .'<p style="margin:0;color:#64748b;font-size:13px;">This code expires in %d minutes. If you did not request this, you can safely ignore this email.</p>'
            .'</div>',
            e(config('app.name')),
            e($actionText),
            e($code),
            $ttlMinutes
        );

        $text = "Your verification code is: {$code}\nThis code expires in {$ttlMinutes} minutes.";

        return $this->sendEmail($toEmail, $fullSubject, $html, $text);
    }
}

