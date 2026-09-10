<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing('citizenProfile');

        $stats = [
            'pending' => Application::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    Application::STATUS_SUBMITTED,
                    Application::STATUS_UNDER_REVIEW,
                    Application::STATUS_INFO_REQUIRED,
                ])->count(),
            'approved' => Application::query()
                ->where('user_id', $user->id)
                ->where('status', Application::STATUS_APPROVED)
                ->count(),
            'active_licences' => License::query()
                ->where('user_id', $user->id)
                ->where('status', License::STATUS_ACTIVE)
                ->whereDate('expiry_date', '>=', today())
                ->count(),
            'profile_complete' => $user->hasCompleteCitizenProfile(),
        ];

        $recentApplications = Application::query()
            ->with(['reservoir', 'category', 'license'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('citizen.dashboard', compact('stats', 'recentApplications'));
    }
}
