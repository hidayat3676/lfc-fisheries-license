<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Reservoir;
use App\Models\ViolationMedia;
use App\Models\ViolationReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ViolationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = ViolationReport::query()
            ->with(['district', 'reservoir'])
            ->where('reporter_user_id', $request->user()->id)
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($reports);
    }

    public function show(Request $request, ViolationReport $report): JsonResponse
    {
        abort_unless($report->reporter_user_id === $request->user()->id, 403);
        $report->load(['district', 'reservoir', 'media']);

        return response()->json(['data' => $report]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'district_id' => ['required_without:reservoir_id', 'nullable', 'integer', 'exists:districts,id'],
            'reservoir_id' => ['nullable', 'integer', 'exists:reservoirs,id'],
            'violation_type' => ['required', Rule::in(array_keys(ViolationReport::TYPES))],
            'description' => ['required', 'string', 'max:2000'],
            'occurred_at' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:30,37'],
            'longitude' => ['nullable', 'numeric', 'between:69,75'],
            'reporter_name' => ['nullable', 'string', 'max:120'],
            'reporter_phone' => ['nullable', 'string', 'max:30'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png', 'max:4096'],
        ]);

        if (! empty($data['reservoir_id'])) {
            $reservoir = Reservoir::query()->findOrFail($data['reservoir_id']);
            $data['district_id'] = $reservoir->district_id;
        }

        $officeId = Office::query()
            ->where('district_id', $data['district_id'])
            ->where('is_active', true)
            ->value('id');

        $report = DB::transaction(function () use ($request, $data, $officeId) {
            $year = now()->format('Y');
            $count = ViolationReport::query()->whereYear('created_at', $year)->count() + 1;

            $report = ViolationReport::query()->create([
                'report_no' => sprintf('VR-%s-%05d', $year, $count),
                'reporter_user_id' => $request->user()?->id,
                'reporter_name' => $data['reporter_name'] ?? $request->user()?->name,
                'reporter_phone' => $data['reporter_phone'] ?? $request->user()?->mobile,
                'district_id' => $data['district_id'],
                'office_id' => $officeId,
                'reservoir_id' => $data['reservoir_id'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'violation_type' => $data['violation_type'],
                'description' => $data['description'],
                'occurred_at' => $data['occurred_at'] ?? now(),
                'status' => ViolationReport::STATUS_PENDING,
            ]);

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    ViolationMedia::query()->create([
                        'violation_report_id' => $report->id,
                        'path' => $photo->store('violations', 'public'),
                        'media_type' => 'image',
                    ]);
                }
            }

            return $report;
        });

        return response()->json([
            'message' => 'Report submitted',
            'data' => $report->load(['district', 'reservoir']),
        ], 201);
    }
}
