<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\License;
use App\Models\Payment;
use App\Models\Reservoir;
use App\Models\ViolationReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $apps = Application::query()->forUserDistricts($user);
        $violations = ViolationReport::query()->forUserDistricts($user);
        $reservoirs = Reservoir::query()->forUserDistricts($user);
        $payments = Payment::query()->whereHas('application.reservoir', function ($q) use ($user) {
            $ids = $user->scopedDistrictIds();
            if ($ids !== null) {
                $q->whereIn('district_id', $ids);
            }
        });

        $stats = [
            'pending_applications' => (clone $apps)->whereIn('status', [
                Application::STATUS_SUBMITTED,
                Application::STATUS_UNDER_REVIEW,
                Application::STATUS_INFO_REQUIRED,
            ])->count(),
            'approved_today' => (clone $apps)->where('status', Application::STATUS_APPROVED)
                ->whereDate('reviewed_at', today())->count(),
            'issued_licences' => License::query()
                ->whereHas('reservoir', function ($q) use ($user) {
                    $ids = $user->scopedDistrictIds();
                    if ($ids !== null) {
                        $q->whereIn('district_id', $ids);
                    }
                })
                ->where('status', License::STATUS_ACTIVE)
                ->count(),
            'open_violations' => (clone $violations)->whereIn('status', [
                ViolationReport::STATUS_PENDING,
                ViolationReport::STATUS_UNDER_INVESTIGATION,
            ])->count(),
            'active_water_bodies' => (clone $reservoirs)->where('is_active', true)->count(),
            'revenue_verified' => (float) (clone $payments)->where('status', 'verified')->sum('amount'),
        ];

        $recentApplications = Application::query()
            ->with(['user', 'reservoir.district', 'category'])
            ->forUserDistricts($user)
            ->latest()
            ->limit(8)
            ->get();

        $recentViolations = ViolationReport::query()
            ->with(['district', 'reservoir'])
            ->forUserDistricts($user)
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentApplications', 'recentViolations'));
    }
}
