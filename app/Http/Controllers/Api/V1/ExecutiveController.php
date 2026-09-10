<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\District;
use App\Models\License;
use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExecutiveController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $request->validate([
            'period' => [
                'nullable',
                'string',
                'in:daily,today,weekly,this_week,monthly,this_month,all_time,all,custom'
            ],
            'date_from' => [
                'nullable',
                'date'
            ],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from'
            ],
        ]);

        $period = strtolower($request->string('period', 'all_time')->toString());
        $from = null;
        $to = null;

        if ($request->filled('date_from')) {
            $from = Carbon::parse($request->input('date_from'))->startOfDay();
        }
        if ($request->filled('date_to')) {
            $to = Carbon::parse($request->input('date_to'))->endOfDay();
        }

        if (! $from && ! $to) {
            match ($period) {
                'daily', 'today' => [
                    $from = now()->startOfDay(),
                    $to = now()->endOfDay(),
                ],
                'weekly', 'this_week' => [
                    $from = now()->startOfWeek(),
                    $to = now()->endOfWeek(),
                ],
                'monthly', 'this_month' => [
                    $from = now()->startOfMonth(),
                    $to = now()->endOfMonth(),
                ],
                default => null,
            };
        }

        // Optimized All-Time Queries
        $allTimeLicensesIssuedCount = License::query()->count();

        $allTimePendingLicensesCount = Application::query()
            ->whereIn('status', [
                Application::STATUS_SUBMITTED,
                Application::STATUS_UNDER_REVIEW,
                Application::STATUS_INFO_REQUIRED,
            ])
            ->count();

        $allTimeRevenue = (float) Application::query()
            ->where('status', Application::STATUS_APPROVED)
            ->sum('fee_amount_snapshot');

        $allDistrictsOfficesCount = Office::query()
            ->where('is_active', true)
            ->count();

        $districtsCount = District::query()
            ->where('is_active', true)
            ->count();

        // Optimized Filtered Queries
        $filteredLicensesIssuedCount = License::query()
            ->when($from, fn ($q) => $q->whereDate('issue_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('issue_date', '<=', $to->toDateString()))
            ->count();

        $filteredPendingLicensesCount = Application::query()
            ->whereIn('status', [
                Application::STATUS_SUBMITTED,
                Application::STATUS_UNDER_REVIEW,
                Application::STATUS_INFO_REQUIRED,
            ])
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->count();

        $filteredRevenue = (float) Application::query()
            ->where('status', Application::STATUS_APPROVED)
            ->when($from, fn ($q) => $q->where('updated_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('updated_at', '<=', $to))
            ->sum('fee_amount_snapshot');

        // District Breakdown via Single Aggregate Query
        $districtBreakdown = Application::query()
            ->where('applications.status', Application::STATUS_APPROVED)
            ->when($from, fn ($q) => $q->where('applications.updated_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('applications.updated_at', '<=', $to))
            ->join('reservoirs', 'applications.reservoir_id', '=', 'reservoirs.id')
            ->join('districts', 'reservoirs.district_id', '=', 'districts.id')
            ->selectRaw('districts.id as district_id, districts.name as district_name, COUNT(applications.id) as licenses_count, SUM(applications.fee_amount_snapshot) as total_revenue')
            ->groupBy('districts.id', 'districts.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn ($item) => [
                'district_id' => (int) $item->district_id,
                'district_name' => $item->district_name,
                'licenses_count' => (int) $item->licenses_count,
                'total_revenue' => (float) $item->total_revenue,
            ]);

        return response()->json([
            'status' => 'success',
            'filter' => [
                'period' => $from || $to ? ($period === 'all_time' ? 'custom' : $period) : 'all_time',
                'date_from' => $from?->toIso8601String(),
                'date_to' => $to?->toIso8601String(),
            ],
            'metrics' => [
                'all_time' => [
                    'all_time_licenses_issued_count' => $allTimeLicensesIssuedCount,
                    'pending_licenses_count' => $allTimePendingLicensesCount,
                    'all_districts_offices_count' => $allDistrictsOfficesCount,
                    'districts_count' => $districtsCount,
                    'all_time_revenue' => $allTimeRevenue,
                ],
                'filtered' => [
                    'licenses_issued_count' => $filteredLicensesIssuedCount,
                    'pending_licenses_count' => $filteredPendingLicensesCount,
                    'revenue_generated' => $filteredRevenue,
                ],
                'district_breakdown' => $districtBreakdown,
            ],
        ]);
    }
}
