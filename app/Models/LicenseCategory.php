<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseCategory extends Model
{
    public const TYPE_TROUT = 'trout';

    public const TYPE_NON_TROUT = 'non_trout';

    protected $fillable = [
        'code',
        'name',
        'urdu_name',
        'type',
        'description',
        'duration_type',
        'duration_days',
        'fee_amount',
        'currency',
        'max_fish_limit',
        'instructions',
        'terms',
        'required_documents',
        'expiry_rule',
        'fixed_expiry_month_day',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fee_amount' => 'decimal:2',
            'required_documents' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function feeLabel(): string
    {
        return number_format((float) $this->fee_amount, 0).' '.$this->currency;
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_TROUT => 'Trout',
            self::TYPE_NON_TROUT => 'Non-Trout',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? 'Non-Trout';
    }
}
