<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationReport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_UNDER_INVESTIGATION = 'under_investigation';

    public const STATUS_RESOLVED = 'resolved';

    public const TYPES = [
        'net_fishing' => 'Net fishing',
        'poison' => 'Poison',
        'explosives' => 'Explosives',
        'illegal_hunting' => 'Illegal hunting',
        'over_fishing' => 'Over fishing',
        'out_of_season' => 'Out of season',
        'other' => 'Other',
    ];

    protected $fillable = [
        'report_no',
        'reporter_user_id',
        'reporter_name',
        'reporter_phone',
        'district_id',
        'office_id',
        'reservoir_id',
        'latitude',
        'longitude',
        'violation_type',
        'description',
        'occurred_at',
        'status',
        'assigned_to',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'occurred_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function reservoir(): BelongsTo
    {
        return $this->belongsTo(Reservoir::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ViolationMedia::class);
    }

    public function scopeForUserDistricts(Builder $query, User $user): Builder
    {
        $ids = $user->scopedDistrictIds();
        if ($ids === null) {
            return $query;
        }

        return $query->whereIn('district_id', $ids);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->violation_type] ?? ucfirst(str_replace('_', ' ', $this->violation_type));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_UNDER_INVESTIGATION => 'Under investigation',
            self::STATUS_RESOLVED => 'Resolved',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
