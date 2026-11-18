<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'start_month' => 'integer',
            'start_day' => 'integer',
            'end_month' => 'integer',
            'end_day' => 'integer',
            'scope_id' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function reservoir(): BelongsTo
    {
        return $this->belongsTo(Reservoir::class, 'scope_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class, 'scope_id');
    }

    public function scopeLabel(): string
    {
        return match ($this->scope_type) {
            'reservoir' => $this->reservoir?->name
                ? 'Water body: '.$this->reservoir->name
                : 'Specific water body (#'.$this->scope_id.')',
            'category' => $this->category?->name
                ? 'Licence category: '.$this->category->name
                : 'Specific licence category (#'.$this->scope_id.')',
            default => 'Global (Province-wide all waters & licences)',
        };
    }

    public function formattedStart(): string
    {
        $day = str_pad((string) $this->start_day, 2, '0', STR_PAD_LEFT);
        $monthName = Carbon::create(2024, $this->start_month, 1)->format('F');

        return "{$day} {$monthName}";
    }

    public function formattedEnd(): string
    {
        $day = str_pad((string) $this->end_day, 2, '0', STR_PAD_LEFT);
        $monthName = Carbon::create(2024, $this->end_month, 1)->format('F');

        return "{$day} {$monthName}";
    }

    public function formattedPeriod(): string
    {
        return $this->formattedStart().' – '.$this->formattedEnd();
    }

    public function durationDays(): int
    {
        $start = Carbon::create(2024, $this->start_month, $this->start_day)->startOfDay();
        $end = Carbon::create(2024, $this->end_month, $this->end_day)->startOfDay();

        if ($start->gt($end)) {
            $end->addYear();
        }

        return $start->diffInDays($end) + 1;
    }

    public function isCurrentlyActive(CarbonInterface|string|null $date = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $date = $date ? Carbon::parse($date)->startOfDay() : now()->startOfDay();
        $year = $date->year;

        $start = Carbon::create($year, $this->start_month, $this->start_day)->startOfDay();
        $end = Carbon::create($year, $this->end_month, $this->end_day)->endOfDay();

        if ($start->lte($end)) {
            return $date->betweenIncluded($start, $end);
        }

        // Year-wrapping interval (e.g. Nov–Feb)
        $startPrev = $start->copy()->subYear();
        $endNext = $end->copy()->addYear();

        return $date->betweenIncluded($start, $endNext) || $date->betweenIncluded($startPrev, $end);
    }

    /**
     * @return array<int, string>
     */
    public static function monthOptions(): array
    {
        return [
            1 => 'January (01)',
            2 => 'February (02)',
            3 => 'March (03)',
            4 => 'April (04)',
            5 => 'May (05)',
            6 => 'June (06)',
            7 => 'July (07)',
            8 => 'August (08)',
            9 => 'September (09)',
            10 => 'October (10)',
            11 => 'November (11)',
            12 => 'December (12)',
        ];
    }
}
