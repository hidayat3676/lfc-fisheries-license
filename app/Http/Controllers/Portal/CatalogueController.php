<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Reservoir;
use App\Services\LicensingPolicyService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    public function index(Request $request): View
    {
        $reservoirs = Reservoir::query()
            ->active()
            ->with(['district', 'office'])
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->integer('district_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('water_body_type', $request->string('type')))
            ->when($request->filled('trout'), fn ($q) => $q->where('trout_type', $request->string('trout')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where('name', 'ilike', $term);
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('portal.catalogue.index', [
            'reservoirs' => $reservoirs,
            'districts' => District::query()->where('is_active', true)->orderBy('name')->get(),
            'types' => config('rflms.water_body_types'),
            'troutTypes' => config('rflms.trout_types'),
        ]);
    }

    public function show(Reservoir $reservoir, LicensingPolicyService $policy): View
    {
        abort_unless($reservoir->is_active, 404);
        $reservoir->load(['district', 'office']);

        $activeLicense = auth()->check() ? $policy->getActiveLicense(auth()->id(), $reservoir->id) : null;

        return view('portal.catalogue.show', compact('reservoir', 'activeLicense'));
    }
}
