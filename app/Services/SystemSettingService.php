<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
    private const CACHE_KEY = 'rflms.system_settings';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $defaults = config('rflms.policy', []);
            $stored = SystemSetting::query()->pluck('value', 'key')->all();

            $merged = $defaults;
            foreach ($stored as $key => $raw) {
                $merged[$key] = $this->decode($raw);
            }

            return $merged;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return $all[$key] ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function set(string $key, mixed $value): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $this->encode($value)]
        );
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $pairs
     */
    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $this->encode($value)]
            );
        }
        Cache::forget(self::CACHE_KEY);
    }

    public function seasonalExpiryMonthDay(): string
    {
        $value = (string) $this->get('seasonal_expiry_month_day', '06-30');

        return preg_match('/^(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $value) ? $value : '06-30';
    }

    public function qrPrivacy(): string
    {
        $mode = (string) $this->get('qr_privacy', 'masked');

        return in_array($mode, ['full', 'masked'], true) ? $mode : 'masked';
    }

    public function requireExplicitELicence(): bool
    {
        return $this->bool('require_explicit_e_licence', true);
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function decode(?string $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }
}
