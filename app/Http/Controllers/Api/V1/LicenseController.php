<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\SystemSettingService;
use App\Support\PrivacyMask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $licenses = License::query()
            ->with([
                'application:id,fishing_start_date,fishing_end_date',
                'reservoir:id,name',
                'reservoir.district:id,name',
                'category:id,name,code',
                'user:id',
                'user.citizenProfile:id,user_id,full_name,father_name,cnic',
                'application.appliedBy:id,name',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($licenses);
    }

    public function show(Request $request, License $license): JsonResponse
    {
        abort_unless($license->user_id === $request->user()->id, 403);
        $license->load(['reservoir.district', 'category', 'application']);

        return response()->json([
            'data' => $license,
            'verify_url' => route('license.verify', $license->qr_token),
            'card_url' => route('citizen.licenses.card', $license),
        ]);
    }

    public function verify(string $token, SystemSettingService $settings): JsonResponse
    {
        $cleanToken = trim($token);

        $license = License::query()
            ->with([
                'user:id,name',
                'user.citizenProfile:id,user_id,full_name,father_name,cnic',
                'reservoir:id,name,district_id',
                'reservoir.district:id,name',
                'category:id,name,code',
                'application:id,fishing_start_date,fishing_end_date',
            ])
            ->where(function ($query) use ($cleanToken) {
                $query->where('qr_token', $cleanToken)
                    ->orWhere('license_no', $cleanToken);

                if (is_numeric($cleanToken)) {
                    $query->orWhere('id', (int) $cleanToken);
                }
            })
            ->first();

        if (! $license) {
            return response()->json([
                'status' => 'error',
                'message' => 'No license found for the given license number.',
            ], 404);
        }

        $valid = $license->status === License::STATUS_ACTIVE
            && $license->expiry_date->endOfDay()->isFuture();

        $mode = $settings->qrPrivacy();
        $holderName = $license->user?->citizenProfile?->full_name ?: ($license->user?->name ?: 'N/A');
        $payload = [
            'status' => $valid ? 'Valid' : ($license->status === License::STATUS_CANCELLED ? 'Cancelled' : 'Expired'),
            'valid' => $valid,
            'license_no' => $license->license_no,
            'holder' => $holderName,
            'full_name' => $holderName,
            'father_name' => $license->user?->citizenProfile?->father_name,
            'reservoir' => $license->reservoir?->name,
            'district' => $license->reservoir?->district?->name,
            'category' => $license->category?->name,
            'issue_date' => optional($license->issue_date)->toDateString(),
            'expiry_date' => optional($license->expiry_date)->toDateString(),
            'fishing_start_date' => optional($license->application?->fishing_start_date)->toDateString(),
            'fishing_end_date' => optional($license->application?->fishing_end_date)->toDateString(),
            'privacy' => $mode,
            'cnic' => $license->user?->citizenProfile?->cnic,
        ];

        if ($settings->bool('show_cnic_on_public_qr', false)) {
            $payload['cnic'] = PrivacyMask::cnic(
                $license->user?->citizenProfile?->cnic,
                $mode
            );
        }

        return response()->json($payload);
    }
}
