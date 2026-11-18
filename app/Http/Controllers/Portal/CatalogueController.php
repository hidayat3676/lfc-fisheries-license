<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Reservoir;
use App\Services\LicensingPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    public function index(Request $request): View
    {
        return view('portal.catalogue.index', $this->buildListingData($request));
    }

    public function reservoirs(Request $request): View
    {
        return view('portal.reservoirs.index', $this->buildListingData($request));
    }

    public function show(Reservoir $reservoir, LicensingPolicyService $policy): View
    {
        abort_unless($reservoir->is_active, 404);
        $reservoir->load(['district', 'office']);

        $activeLicense = auth()->check() ? $policy->getActiveLicense(auth()->id(), $reservoir->id) : null;

        return view('portal.catalogue.show', compact('reservoir', 'activeLicense'));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildListingData(Request $request): array
    {
        $hasLicenseType = Schema::hasColumn('reservoirs', 'license_type');

        $query = Reservoir::query()
            ->active()
            ->with(['district', 'office'])
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->integer('district_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('water_body_type', $request->string('type')))
            ->when($request->filled('trout'), fn ($q) => $q->where('trout_type', $request->string('trout')))
            ->when($request->filled('eligibility'), function ($q) use ($request) {
                $elig = (string) $request->string('eligibility');
                if ($elig === 'open') {
                    $q->where('is_open_for_licensing', true);
                } elseif ($elig === 'closed') {
                    $q->where('is_open_for_licensing', false);
                }
            })
            ->when($request->filled('permit') && $hasLicenseType, function ($q) use ($request) {
                $permitTerm = '%'.$request->string('permit').'%';
                $q->where('license_type', 'ilike', $permitTerm);
            })
            ->when($request->filled('q'), function ($q) use ($request, $hasLicenseType) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term, $hasLicenseType) {
                    $inner->where('name', 'ilike', $term)
                        ->orWhere('description', 'ilike', $term)
                        ->orWhere('species_notes', 'ilike', $term)
                        ->orWhereHas('district', fn ($dq) => $dq->where('name', 'ilike', $term));

                    if ($hasLicenseType) {
                        $inner->orWhere('license_type', 'ilike', $term);
                    }
                });
            });

        $totalMatching = (clone $query)->count();

        $reservoirs = $query
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return [
            'reservoirs' => $reservoirs,
            'totalMatching' => $totalMatching,
            'districts' => District::query()->where('is_active', true)->orderBy('name')->get(),
            'types' => config('rflms.water_body_types'),
            'troutTypes' => config('rflms.trout_types'),
            'permitOptions' => [
                'Daily Angling License (Trout)' => 'Daily Angling License (Trout)',
                'Daily Angling License (Non-Trout)' => 'Daily Angling License (Non-Trout)',
                'Rod & Line' => 'Rod & Line Only',
                'Net' => 'Net (Dipnet, Hand Net, Casting Net)',
                'DTA' => 'DTA (Trout)',
            ],
        ];
    }
}
