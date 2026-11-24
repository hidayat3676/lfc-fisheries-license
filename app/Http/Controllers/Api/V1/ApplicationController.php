<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use App\Services\ApplicationWorkflowService;
use App\Services\LicensingPolicyService;
use App\Services\PaymirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $apps = Application::query()
            ->with(['reservoir.district', 'category', 'license'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($apps);
    }

    public function show(Request $request, Application $application): JsonResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);
        $application->load(['reservoir.district', 'category', 'license', 'payments']);

        return response()->json(['data' => $application]);
    }

    public function store(
        Request $request,
        LicensingPolicyService $policy,
        ApplicationWorkflowService $workflow,
        PaymirService $paymir
    ): JsonResponse {
        $user = $request->user()->loadMissing('citizenProfile');
        if (! $user->hasCompleteCitizenProfile()) {
            return response()->json([
                'message' => 'Complete your profile before applying.',
                'code' => 'PROFILE_INCOMPLETE',
            ], 422);
        }

        $data = $request->validate([
            'district_id' => ['required_without:reservoir_id', 'nullable', 'integer', 'exists:districts,id'],
            'reservoir_id' => ['nullable', 'integer', 'exists:reservoirs,id'],
            'category_id' => ['required', 'integer', Rule::exists('license_categories', 'id')->where('is_active', true)],
            'fishing_start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', Rule::in(Application::paymentMethods())],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'accept_terms' => ['accepted'],
        ]);

        $method = $data['payment_method'];

        $category = LicenseCategory::query()->findOrFail($data['category_id']);
        $categoryType = $category->type ?: LicenseCategory::TYPE_NON_TROUT;

        if (! empty($data['reservoir_id'])) {
            $reservoir = Reservoir::query()->findOrFail($data['reservoir_id']);
        } else {
            $reservoir = Reservoir::query()
                ->where('district_id', (int) $data['district_id'])
                ->where('is_active', true)
                ->where('trout_type', $categoryType)
                ->orderByDesc('is_open_for_licensing')
                ->orderBy('name')
                ->first();

            if (! $reservoir) {
                return response()->json([
                    'message' => 'No active '.strtolower($category->typeLabel()).' water bodies are available in the selected district.',
                    'code' => 'NO_RESERVOIRS_IN_DISTRICT',
                ], 422);
            }
        }

        if (! $reservoir->is_active || ! $reservoir->allowsELicence()) {
            return response()->json([
                'message' => 'Water body is not open for licensing.',
                'code' => 'NOT_ELIGIBLE',
            ], 422);
        }

        if ($policy->hasValidLicense($user->id, $reservoir->id)) {
            return response()->json([
                'message' => 'You already have a valid active licence for this water body. Re-applying is not allowed until your current licence expires.',
                'code' => 'ACTIVE_LICENSE_EXISTS',
            ], 422);
        }

        $isSeasonal = $category->duration_type === 'seasonal'
            || in_array($category->expiry_rule, ['fixed_date', 'season_end'], true);

        if ($isSeasonal) {
            $data['fishing_start_date'] = now()->toDateString();
        } elseif (empty($data['fishing_start_date'])) {
            return response()->json([
                'message' => 'The fishing start date field is required.',
                'errors' => ['fishing_start_date' => ['The fishing start date field is required.']],
            ], 422);
        }

        $closed = $policy->isClosedOn($data['fishing_start_date'], $reservoir->id, $category->id);
        if ($closed) {
            return response()->json([
                'message' => 'Fishing closed on this date: '.$closed->reason,
                'code' => 'CLOSED_SEASON',
            ], 422);
        }

        $end = $policy->calculateEndDate($category, $data['fishing_start_date']);
        $receiptPath = $request->hasFile('payment_receipt')
            ? $request->file('payment_receipt')->store('receipts', 'public')
            : null;

        $isOnline = Application::isOnlinePaymentMethod($data['payment_method']) || Application::isOnlinePaymentMethod($method);
        $initialStatus = $isOnline ? Application::STATUS_PENDING : Application::STATUS_UNDER_REVIEW;

        $application = DB::transaction(function () use ($user, $reservoir, $category, $data, $end, $receiptPath, $workflow, $method, $initialStatus) {
            $application = Application::query()->create([
                'application_no' => $workflow->nextApplicationNo(),
                'user_id' => $user->id,
                'applied_by' => $user->id,
                'reservoir_id' => $reservoir->id,
                'category_id' => $category->id,
                'fishing_start_date' => $data['fishing_start_date'],
                'fishing_end_date' => $end->toDateString(),
                'fee_amount_snapshot' => $category->fee_amount,
                'currency' => $category->currency,
                'status' => $initialStatus,
                'payment_method' => $method,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_receipt_path' => $receiptPath,
                'psid_code' => null,
            ]);

            if ($receiptPath) {
                $application->documents()->create([
                    'doc_type' => 'payment_receipt',
                    'path' => $receiptPath,
                ]);
            }

            $workflow->createPaymentLedger($application);

            return $application;
        });

        $paymirResult = null;
        if ($isOnline) {
            $paymirResult = $paymir->generatePsidForApplication($application, $user);
        }

        $workflow->notifySubmitted($application);

        return response()->json([
            'message' => 'Application submitted',
            'psid' => $application->psid_code,
            'paymir' => $paymirResult,
            'data' => $application->fresh(['reservoir', 'category', 'voucher']),
        ], 201);
    }
}
