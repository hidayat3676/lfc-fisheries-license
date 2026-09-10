<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Office extends Model
{
    protected $fillable = [
        'district_id',
        'name',
        'phone',
        'email',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function reservoirs(): HasMany
    {
        return $this->hasMany(Reservoir::class);
    }

    public function licenses(): HasManyThrough
    {
        return $this->hasManyThrough(License::class, Reservoir::class, 'office_id', 'reservoir_id');
    }

    public function applications(): HasManyThrough
    {
        return $this->hasManyThrough(Application::class, Reservoir::class, 'office_id', 'reservoir_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_offices')->withTimestamps();
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
}
