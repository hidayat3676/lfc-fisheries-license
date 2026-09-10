<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function districts(): JsonResponse
    {
        $districts = District::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['data' => $districts]);
    }

    public function reservoirs(Request $request): JsonResponse
    {
        $items = Reservoir::query()
            ->active()
            ->with(['district:id,name', 'office:id,name,phone,email'])
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->integer('district_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('water_body_type', $request->string('type')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'ilike', '%'.$request->string('q').'%'))
            ->orderBy('name')
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($items);
    }

    public function showReservoir(Reservoir $reservoir): JsonResponse
    {
        abort_unless($reservoir->is_active, 404);
        $reservoir->load(['district', 'office']);

        return response()->json([
            'data' => [
                'id' => $reservoir->id,
                'name' => $reservoir->name,
                'district' => $reservoir->district,
                'office' => $reservoir->office,
                'water_body_type' => $reservoir->water_body_type,
                'trout_type' => $reservoir->trout_type,
                'description' => $reservoir->description,
                'species_notes' => $reservoir->species_notes,
                'latitude' => $reservoir->latitude,
                'longitude' => $reservoir->longitude,
                'directions_url' => $reservoir->mapsUrl(),
                'is_open_for_licensing' => $reservoir->is_open_for_licensing,
                'lease_notes' => $reservoir->lease_notes,
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = LicenseCategory::query()
            ->active()
            ->orderBy('fee_amount')
            ->get([
                'id', 'code', 'name', 'description', 'duration_type', 'duration_days',
                'fee_amount', 'currency', 'expiry_rule', 'fixed_expiry_month_day',
                'instructions', 'terms',
            ]);

        return response()->json(['data' => $categories]);
    }
}
