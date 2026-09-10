<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_districts')->withTimestamps();
    }

    public function citizenProfiles(): HasMany
    {
        return $this->hasMany(CitizenProfile::class, 'residence_district_id');
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    public function reservoirs(): HasMany
    {
        return $this->hasMany(Reservoir::class);
    }
}
