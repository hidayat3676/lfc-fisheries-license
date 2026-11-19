<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClosedSeason;
use App\Models\District;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use App\Services\LicensingPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function licenseApplicationData(Request $request, LicensingPolicyService $policy): JsonResponse
    {
        $user = $request->user();
        $scopedIds = ($user && $user->isAdminStaff()) ? $user->scopedDistrictIds() : null;

        $districts = District::query()
            ->where('is_active', true)
            ->when($scopedIds !== null, fn ($q) => $q->whereIn('id', $scopedIds))
            ->with(['reservoirs' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(function (District $district) {
                return [
                    'id' => $district->id,
                    'name' => $district->name,
                    'urdu_name' => $district->urdu_name,
                    'code' => $district->code,
                    'reservoirs' => $district->reservoirs->map(function (Reservoir $r) {
                        $lat = $r->latitude ?? $r->start_lat;
                        $lng = $r->longitude ?? $r->start_lng;
                        $mapUrl = ($lat !== null && $lng !== null)
                            ? 'https://www.google.com/maps?q='.$lat.','.$lng
                            : $r->mapsUrl();

                        return [
                            'id' => $r->id,
                            'name' => $r->name,
                            'district_id' => $r->district_id,
                            'office_id' => $r->office_id,
                            'water_body_type' => $r->water_body_type,
                            'type_label' => $r->typeLabel(),
                            'trout_type' => $r->trout_type,
                            'trout_label' => $r->troutLabel(),
                            'license_type' => $r->licensePermitsLabel(),
                            'latitude' => $lat,
                            'longitude' => $lng,
                            'start_lat' => $r->start_lat,
                            'start_lng' => $r->start_lng,
                            'end_lat' => $r->end_lat,
                            'end_lng' => $r->end_lng,
                            'google_maps_url' => $mapUrl,
                            'directions_url' => $mapUrl,
                            'image_url' => $r->imageUrl(),
                            'allows_e_licence' => $r->allowsELicence(),
                            'description' => $r->description,
                            'species_notes' => $r->species_notes,
                            'length_km_notes' => $r->length_km_notes,
                        ];
                    })->values(),
                ];
            })->values();

        $todayStr = now()->toDateString();
        $categories = LicenseCategory::query()
            ->active()
            ->orderBy('fee_amount')
            ->get()
            ->map(function (LicenseCategory $cat) use ($policy, $todayStr) {
                $isSeasonal = $cat->duration_type === 'seasonal'
                    || in_array($cat->expiry_rule, ['fixed_date', 'season_end'], true)
                    || str_contains(strtolower((string) $cat->name), 'seasonal');
                $endDate = $policy->calculateEndDate($cat, $todayStr);

                return [
                    'id' => $cat->id,
                    'code' => $cat->code,
                    'name' => $cat->name,
                    'urdu_name' => $cat->urdu_name,
                    'description' => $cat->description,
                    'duration_type' => $cat->duration_type,
                    'duration_days' => $cat->duration_days,
                    'is_seasonal' => $isSeasonal,
                    'fee_amount' => $cat->fee_amount,
                    'currency' => $cat->currency,
                    'fee_label' => $cat->feeLabel(),
                    'max_fish_limit' => $cat->max_fish_limit,
                    'expiry_rule' => $cat->expiry_rule,
                    'fixed_expiry_month_day' => $cat->fixed_expiry_month_day,
                    'from_date' => $todayStr,
                    'end_date' => $endDate->toDateString(),
                    'end_date_formatted' => $endDate->format('d M Y'),
                    'instructions' => $cat->instructions,
                    'terms' => $cat->terms,
                ];
            })->values();

        $closedSeasons = ClosedSeason::query()
            ->active()
            ->with(['reservoir:id,name,district_id', 'category:id,code,name'])
            ->orderByRaw("CASE WHEN scope_type = 'global' THEN 1 WHEN scope_type = 'reservoir' THEN 2 ELSE 3 END")
            ->orderBy('start_month')
            ->orderBy('start_day')
            ->get()
            ->map(function (ClosedSeason $season) {
                return [
                    'id' => $season->id,
                    'scope_type' => $season->scope_type,
                    'scope_id' => $season->scope_id,
                    'scope_label' => $season->scopeLabel(),
                    'start_month' => $season->start_month,
                    'start_day' => $season->start_day,
                    'formatted_start' => $season->formattedStart(),
                    'end_month' => $season->end_month,
                    'end_day' => $season->end_day,
                    'formatted_end' => $season->formattedEnd(),
                    'formatted_period' => $season->formattedPeriod(),
                    'duration_days' => $season->durationDays(),
                    'reason' => $season->reason,
                    'is_active' => $season->is_active,
                    'is_currently_active' => $season->isCurrentlyActive(),
                ];
            })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'districts' => $districts,
                'license_categories' => $categories,
                'closed_seasons' => $closedSeasons,
                'payment_methods' => [
                    ['key' => '1bill', 'label' => '1Bill / PAYMIR PSID'],
                    ['key' => 'cash', 'label' => 'Cash'],
                ],
            ],
        ]);
    }

    public function districts(): JsonResponse
    {
        $districts = District::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'urdu_name', 'code']);

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
                'id', 'code', 'name', 'urdu_name', 'description', 'duration_type', 'duration_days',
                'fee_amount', 'currency', 'expiry_rule', 'fixed_expiry_month_day',
                'instructions', 'terms',
            ]);

        return response()->json(['data' => $categories]);
    }
}
