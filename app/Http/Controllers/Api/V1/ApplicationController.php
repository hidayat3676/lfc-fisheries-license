<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use App\Services\ApplicationWorkflowService;
use App\Services\LicensingPolicyService;
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
        ApplicationWorkflowService $workflow
    ): JsonResponse {
        $user = $request->user()->loadMissing('citizenProfile');
        if (! $user->hasCompleteCitizenProfile()) {
            return response()->json([
                'message' => 'Complete your profile before applying.',
                'code' => 'PROFILE_INCOMPLETE',
            ], 422);
        }

        $data = $request->validate([
            'reservoir_id' => ['required', 'integer', 'exists:reservoirs,id'],
            'category_id' => ['required', 'integer', Rule::exists('license_categories', 'id')->where('is_active', true)],
            'fishing_start_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', Rule::in(Application::paymentMethods())],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'accept_terms' => ['accepted'],
        ]);

        $method = $data['payment_method'] === Application::PAYMENT_METHOD_ONLINE_TRANSFER ? Application::PAYMENT_METHOD_BANK_TRANSFER : $data['payment_method'];
        if (in_array($method, [Application::PAYMENT_METHOD_BANK_TRANSFER, Application::PAYMENT_METHOD_BANK_DEPOSIT], true)) {
            $request->validate([
                'payment_reference' => ['required', 'string', 'max:120'],
                'payment_receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            ]);
        }

        $reservoir = Reservoir::query()->findOrFail($data['reservoir_id']);
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

        $category = LicenseCategory::query()->findOrFail($data['category_id']);
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
        $psid = $method === Application::PAYMENT_METHOD_1BILL
            ? 'PSID-DEMO-'.strtoupper(substr(md5($user->id.'|'.$reservoir->id.'|'.now()->timestamp), 0, 10))
            : null;

        $application = DB::transaction(function () use ($user, $reservoir, $category, $data, $end, $receiptPath, $psid, $workflow, $method) {
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
                'status' => Application::STATUS_UNDER_REVIEW,
                'payment_method' => $method,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_receipt_path' => $receiptPath,
                'psid_code' => $psid,
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

        $workflow->notifySubmitted($application);

        return response()->json([
            'message' => 'Application submitted',
            'data' => $application->load(['reservoir', 'category']),
        ], 201);
    }
}
