<?php

namespace App\Services;

use App\Models\ClosedSeason;
use App\Models\License;
use App\Models\LicenseCategory;
use App\Services\SystemSettingService;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class LicensingPolicyService
{
    public function calculateEndDate(LicenseCategory $category, CarbonInterface|string $start): Carbon
    {
        $start = Carbon::parse($start)->startOfDay();
        $seasonalExpiry = app(SystemSettingService::class)->seasonalExpiryMonthDay();

        return match ($category->expiry_rule) {
            'fixed_date' => $this->nextFixedDate(
                $start,
                (string) ($category->fixed_expiry_month_day ?: $seasonalExpiry)
            ),
            'season_end' => $this->nextFixedDate(
                $start,
                $category->fixed_expiry_month_day ?: $seasonalExpiry
            ),
            default => $start->copy()->addDays(max(1, (int) ($category->duration_days ?: 1)) - 1),
        };
    }

    public function isClosedOn(CarbonInterface|string $date, ?int $reservoirId = null, ?int $categoryId = null): ?ClosedSeason
    {
        $date = Carbon::parse($date)->startOfDay();
        $seasons = ClosedSeason::query()->active()->get();

        foreach ($seasons as $season) {
            if ($season->scope_type === 'reservoir' && (int) $season->scope_id !== (int) $reservoirId) {
                continue;
            }
            if ($season->scope_type === 'category' && (int) $season->scope_id !== (int) $categoryId) {
                continue;
            }
            if ($this->dateInSeason($date, $season)) {
                return $season;
            }
        }

        return null;
    }

    public function getActiveLicense(int $userId, int $reservoirId): ?License
    {
        return License::query()
            ->where('user_id', $userId)
            ->where('reservoir_id', $reservoirId)
            ->where('status', License::STATUS_ACTIVE)
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->latest('expiry_date')
            ->first();
    }

    public function hasValidLicense(int $userId, int $reservoirId): bool
    {
        return $this->getActiveLicense($userId, $reservoirId) !== null;
    }

    private function nextFixedDate(Carbon $start, string $monthDay): Carbon
    {
        [$month, $day] = array_map('intval', explode('-', $monthDay));
        $candidate = Carbon::create($start->year, $month, $day)->startOfDay();
        if ($candidate->lt($start)) {
            $candidate->addYear();
        }

        return $candidate;
    }

    private function dateInSeason(Carbon $date, ClosedSeason $season): bool
    {
        $year = $date->year;
        $start = Carbon::create($year, $season->start_month, $season->start_day)->startOfDay();
        $end = Carbon::create($year, $season->end_month, $season->end_day)->endOfDay();

        if ($start->lte($end)) {
            return $date->betweenIncluded($start, $end);
        }

        // wraps year-end (e.g. Nov–Feb)
        $startPrev = $start->copy()->subYear();
        $endNext = $end->copy()->addYear();

        return $date->betweenIncluded($start, $endNext) || $date->betweenIncluded($startPrev, $end);
    }
}
