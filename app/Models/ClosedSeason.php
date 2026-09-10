<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClosedSeason extends Model
{
    protected $fillable = [
        'scope_type',
        'scope_id',
        'start_month',
        'start_day',
        'end_month',
        'end_day',
        'reason',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
