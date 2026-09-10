<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitizenProfile extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'father_name',
        'cnic',
        'dob',
        'gender',
        'address',
        'residence_district_id',
        'province',
        'emergency_contact',
        'photo_path',
    ];

    protected $appends = [
        'photo_url',
    ];

    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        $baseUrl = config('app.image_base_url');

        if (! $baseUrl) {
            if ($forwardedHost = request()->header('x-forwarded-host')) {
                $scheme = request()->header('x-forwarded-proto') ?: request()->getScheme();
                $baseUrl = $scheme . '://' . $forwardedHost;
            } else {
                $baseUrl = request()->getSchemeAndHttpHost();
            }
        }

        $path = ltrim($this->photo_path, '/');
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }
        if (! str_starts_with($path, 'storage/')) {
            $path = 'storage/' . $path;
        }

        return rtrim($baseUrl, '/') . '/' . $path;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photoUrl();
    }

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function residenceDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'residence_district_id');
    }

    public function isComplete(): bool
    {
        return filled($this->full_name)
            && filled($this->father_name)
            && filled($this->cnic)
            && filled($this->dob)
            && filled($this->gender)
            && filled($this->address)
            && filled($this->residence_district_id)
            && filled($this->province)
            && filled($this->emergency_contact);
    }

    /**
     * Find citizen profile by CNIC (supports formatted or unformatted CNIC).
     */
    public static function findByCnic(string $cnic): ?self
    {
        $cleanCnic = trim($cnic);
        $digitsOnly = preg_replace('/[^0-9]/', '', $cleanCnic);

        if (empty($cleanCnic)) {
            return null;
        }

        return static::query()
            ->where(function ($query) use ($cleanCnic, $digitsOnly) {
                $query->where('cnic', $cleanCnic);
                if (! empty($digitsOnly)) {
                    $query->orWhereRaw("REPLACE(cnic, '-', '') = ?", [$digitsOnly]);
                }
            })
            ->first();
    }
}
