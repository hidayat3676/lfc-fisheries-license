<?php

namespace App\Models;

use App\Services\SystemSettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservoir extends Model
{
    protected $fillable = [
        'seed_id',
        'district_id',
        'office_id',
        'name',
        'water_body_type',
        'trout_type',
        'description',
        'latitude',
        'longitude',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'trout_stretch_notes',
        'reserve_area_notes',
        'lease_notes',
        'length_km_notes',
        'species_notes',
        'coordinates_raw',
        'directions_url',
        'image_path',
        'is_open_for_licensing',
        'is_active',
        'needs_review',
        'source_file',
    ];

    protected $appends = [
        'allows_e_licence',
        'image_url',
    ];

    public function getAllowsELicenceAttribute(): bool
    {
        return $this->allowsELicence();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->imageUrl();
    }

    public function imageUrl(): ?string
    {
        if ($this->image_path) {
            return \Illuminate\Support\Facades\Storage::url($this->image_path);
        }

        return null;
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'start_lat' => 'float',
            'start_lng' => 'float',
            'end_lat' => 'float',
            'end_lng' => 'float',
            'is_open_for_licensing' => 'boolean',
            'is_active' => 'boolean',
            'needs_review' => 'boolean',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForUserDistricts(Builder $query, User $user): Builder
    {
        $ids = $user->scopedDistrictIds();
        if ($ids === null) {
            return $query;
        }

        return $query->whereIn('district_id', $ids);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function mapsUrl(): ?string
    {
        if ($this->directions_url) {
            return $this->directions_url;
        }

        if (! $this->hasCoordinates()) {
            return null;
        }

        return 'https://www.google.com/maps?q='.$this->latitude.','.$this->longitude;
    }

    public function typeLabel(): string
    {
        return match ($this->water_body_type) {
            'dam' => 'Dam / Reservoir',
            'river' => 'River',
            'stream' => 'Stream',
            'canal' => 'Canal',
            'headworks' => 'Headworks',
            default => 'Other',
        };
    }

    public function troutLabel(): string
    {
        return match ($this->trout_type) {
            'trout' => 'Trout',
            'non_trout' => 'Non-trout',
            'mixed' => 'Mixed',
            default => 'Unknown',
        };
    }

    /**
     * Whether citizens may apply for an e-licence on this water body.
     */
    public function allowsELicence(?bool $requireExplicit = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $requireExplicit ??= app(SystemSettingService::class)->requireExplicitELicence();

        if ($requireExplicit) {
            return $this->is_open_for_licensing === true;
        }

        return $this->is_open_for_licensing !== false;
    }
}
