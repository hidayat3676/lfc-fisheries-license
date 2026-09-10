<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'license_no',
        'application_id',
        'user_id',
        'reservoir_id',
        'category_id',
        'issue_date',
        'expiry_date',
        'status',
        'qr_token',
        'pdf_path',
        'issued_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function getFishingStartDateAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->application?->fishing_start_date ?? $this->issue_date;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservoir(): BelongsTo
    {
        return $this->belongsTo(Reservoir::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class, 'category_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isValidNow(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expiry_date->endOfDay()->isFuture();
    }

    public function scopeForUserDistricts(Builder $query, User $user): Builder
    {
        $ids = $user->scopedDistrictIds();
        if ($ids === null) {
            return $query;
        }

        return $query->whereHas('reservoir', fn ($q) => $q->whereIn('district_id', $ids));
    }

    /**
     * Find licenses belonging to citizen with the given CNIC.
     */
    public static function findByCnic(string $cnic)
    {
        $cleanCnic = trim($cnic);
        $digitsOnly = preg_replace('/[^0-9]/', '', $cleanCnic);

        return static::query()
            ->whereHas('user.citizenProfile', function ($query) use ($cleanCnic, $digitsOnly) {
                $query->where('cnic', $cleanCnic);
                if (! empty($digitsOnly)) {
                    $query->orWhereRaw("REPLACE(cnic, '-', '') = ?", [$digitsOnly]);
                }
            });
    }
}
