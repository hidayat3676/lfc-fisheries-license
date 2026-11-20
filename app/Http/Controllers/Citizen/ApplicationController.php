<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\District;
use App\Models\License;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use App\Services\ApplicationWorkflowService;
use App\Services\LicensingPolicyService;
use App\Services\PaymirService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = Application::query()
            ->with(['reservoir.district', 'category', 'license'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        $licenses = License::query()
            ->with(['reservoir.district', 'category'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('citizen.applications.index', compact('applications', 'licenses'));
    }

    public function create(
        Request $request,
        LicensingPolicyService $policy,
        ?Reservoir $reservoir = null
    ): View|RedirectResponse {
        if ($reservoir && ! $reservoir->exists) {
            $reservoir = null;
        }

        if ($reservoir) {
            abort_unless($reservoir->is_active, 404);
        }

        $request->user()->loadMissing('citizenProfile');
        if (! $request->user()->hasCompleteCitizenProfile()) {
            $intended = $reservoir
                ? route('citizen.applications.create', $reservoir)
                : route('citizen.applications.apply');
            $request->session()->put('url.intended', $intended);

            return redirect()
                ->route('citizen.profile.edit')
                ->with('status', 'Please complete your profile before applying for a licence.');
        }

        if ($reservoir) {
            if (! $reservoir->allowsELicence()) {
                return redirect()
                    ->route('catalogue.show', $reservoir)
                    ->withErrors(['licensing' => 'This water body is not open for e-licence applications.']);
            }

            if ($policy->hasValidLicense($request->user()->id, $reservoir->id)) {
                return redirect()
                    ->route('catalogue.show', $reservoir)
                    ->withErrors(['licensing' => 'You already have a valid active licence for this water body. Re-applying is not allowed until your current licence expires.']);
            }
        }

        $districts = District::query()
            ->where('is_active', true)
            ->with(['reservoirs' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $categories = LicenseCategory::query()->active()->orderBy('fee_amount')->get();

        $selectedDistrictId = $request->filled('district_id')
            ? $request->integer('district_id')
            : $reservoir?->district_id;

        return view('citizen.applications.create', [
            'reservoir' => $reservoir?->load(['district', 'office']),
            'districts' => $districts,
            'selectedDistrictId' => $selectedDistrictId,
            'categories' => $categories,
            'paymentMethods' => [
                '1bill' => '1Bill / PAYMIR PSID (Online Payment)',
                'counter_cash' => 'Cash',
            ],
        ]);
    }

    public function store(
        Request $request,
        LicensingPolicyService $policy,
        ApplicationWorkflowService $workflow,
        PaymirService $paymir,
        ?Reservoir $reservoir = null
    ): RedirectResponse {
        if ($reservoir && ! $reservoir->exists) {
            $reservoir = null;
        }

        if ($reservoir) {
            abort_unless($reservoir->is_active, 404);
        }

        $request->user()->loadMissing('citizenProfile');
        if (! $request->user()->hasCompleteCitizenProfile()) {
            return redirect()
                ->route('citizen.profile.edit')
                ->with('status', 'Please complete your profile before applying for a licence.');
        }

        $data = $request->validate([
            'district_id' => [$reservoir ? 'nullable' : 'required', 'integer', 'exists:districts,id'],
            'category_id' => ['required', 'integer', Rule::exists('license_categories', 'id')->where('is_active', true)],
            'fishing_start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', Rule::in(Application::paymentMethods())],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'accept_terms' => ['accepted'],
        ]);

        $category = LicenseCategory::query()->findOrFail($data['category_id']);
        $categoryType = $category->type ?: LicenseCategory::TYPE_NON_TROUT;

        if (! empty($data['district_id']) && (! $reservoir || (int) $reservoir->district_id !== (int) $data['district_id'] || $reservoir->trout_type !== $categoryType)) {
            $reservoir = Reservoir::query()
                ->where('district_id', (int) $data['district_id'])
                ->where('is_active', true)
                ->where('trout_type', $categoryType)
                ->orderByDesc('is_open_for_licensing')
                ->orderBy('name')
                ->first();

            if (! $reservoir) {
                throw ValidationException::withMessages([
                    'district_id' => 'No active '.strtolower($category->typeLabel()).' water bodies are available in the selected district.',
                ]);
            }
        }

        if (! $reservoir->allowsELicence()) {
            throw ValidationException::withMessages([
                'reservoir' => 'This water body is not open for e-licence applications.',
            ]);
        }

        if ($policy->hasValidLicense($request->user()->id, $reservoir->id)) {
            throw ValidationException::withMessages([
                'reservoir' => 'You already have a valid active licence for this water body. Re-applying is not allowed until your current licence expires.',
            ]);
        }

        if ($data['payment_method'] === Application::PAYMENT_METHOD_CASH) {
            $data['payment_method'] = Application::PAYMENT_METHOD_COUNTER_CASH;
        }

        $isSeasonal = $category->duration_type === 'seasonal'
            || in_array($category->expiry_rule, ['fixed_date', 'season_end'], true);

        if ($isSeasonal) {
            $start = now()->toDateString();
            $data['fishing_start_date'] = $start;
        } else {
            if (empty($data['fishing_start_date'])) {
                throw ValidationException::withMessages([
                    'fishing_start_date' => 'The fishing start date field is required.',
                ]);
            }
            $start = $data['fishing_start_date'];
        }

        $closed = $policy->isClosedOn($start, $reservoir->id, $category->id);
        if ($closed) {
            throw ValidationException::withMessages([
                'fishing_start_date' => 'Fishing is not allowed on this date ('.$closed->reason.').',
            ]);
        }

        $end = $policy->calculateEndDate($category, $start);
        $receiptPath = null;
        if ($request->hasFile('payment_receipt')) {
            $receiptPath = $request->file('payment_receipt')->store('receipts', 'public');
        }

        $initialStatus = Application::isOnlinePaymentMethod($data['payment_method'])
            ? Application::STATUS_PENDING
            : Application::STATUS_UNDER_REVIEW;

        $application = DB::transaction(function () use ($request, $reservoir, $category, $data, $end, $receiptPath, $workflow, $initialStatus) {
            $application = Application::query()->create([
                'application_no' => $workflow->nextApplicationNo(),
                'user_id' => $request->user()->id,
                'reservoir_id' => $reservoir->id,
                'category_id' => $category->id,
                'fishing_start_date' => $data['fishing_start_date'],
                'fishing_end_date' => $end->toDateString(),
                'fee_amount_snapshot' => $category->fee_amount,
                'currency' => $category->currency,
                'status' => $initialStatus,
                'payment_method' => $data['payment_method'],
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

        $statusMsg = 'Application submitted for review.';
        if (Application::isOnlinePaymentMethod($data['payment_method'])) {
            $paymirResult = $paymir->generatePsidForApplication($application, $request->user());
            $statusMsg = 'Application submitted for review. PAYMIR PSID: '.$paymirResult['psid'];
        }

        $workflow->notifySubmitted($application);

        return redirect()
            ->route('citizen.applications.success', $application)
            ->with('status', $statusMsg);
    }

    public function success(Request $request, Application $application): View
    {
        abort_unless($application->user_id === $request->user()->id, 403);
        $application->load(['reservoir.district', 'category', 'license', 'payments', 'user.citizenProfile']);

        return view('citizen.applications.success', compact('application'));
    }

    public function show(Request $request, Application $application): View
    {
        abort_unless($application->user_id === $request->user()->id, 403);
        $application->load(['reservoir.district', 'category', 'license', 'payments']);

        return view('citizen.applications.show', compact('application'));
    }
}
