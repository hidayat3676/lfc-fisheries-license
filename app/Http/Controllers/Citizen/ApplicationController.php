<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\License;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use App\Services\ApplicationWorkflowService;
use App\Services\LicensingPolicyService;
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

    public function create(Request $request, Reservoir $reservoir, LicensingPolicyService $policy): View|RedirectResponse
    {
        abort_unless($reservoir->is_active, 404);

        $request->user()->loadMissing('citizenProfile');
        if (! $request->user()->hasCompleteCitizenProfile()) {
            $request->session()->put('url.intended', route('citizen.applications.create', $reservoir));

            return redirect()
                ->route('citizen.profile.edit')
                ->with('status', 'Please complete your profile before applying for a licence.');
        }

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

        $categories = LicenseCategory::query()->active()->orderBy('fee_amount')->get();

        return view('citizen.applications.create', [
            'reservoir' => $reservoir->load(['district', 'office']),
            'categories' => $categories,
            'paymentMethods' => [
                'bank_transfer' => 'Online bank transfer',
                'bank_deposit' => 'Bank deposit',
                '1bill' => '1Bill / PSID (display only)',
            ],
        ]);
    }

    public function store(
        Request $request,
        Reservoir $reservoir,
        LicensingPolicyService $policy,
        ApplicationWorkflowService $workflow
    ): RedirectResponse {
        abort_unless($reservoir->is_active, 404);

        $request->user()->loadMissing('citizenProfile');
        if (! $request->user()->hasCompleteCitizenProfile()) {
            return redirect()
                ->route('citizen.profile.edit')
                ->with('status', 'Please complete your profile before applying for a licence.');
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

        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('license_categories', 'id')->where('is_active', true)],
            'fishing_start_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'bank_deposit', '1bill'])],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'accept_terms' => ['accepted'],
        ]);

        if (in_array($data['payment_method'], ['bank_transfer', 'bank_deposit'], true)) {
            $request->validate([
                'payment_reference' => ['required', 'string', 'max:120'],
                'payment_receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            ]);
        }

        $category = LicenseCategory::query()->findOrFail($data['category_id']);
        $start = $data['fishing_start_date'];

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

        $psid = $data['payment_method'] === '1bill'
            ? 'PSID-DEMO-'.strtoupper(substr(md5($request->user()->id.'|'.$reservoir->id.'|'.now()->timestamp), 0, 10))
            : null;

        $application = DB::transaction(function () use ($request, $reservoir, $category, $data, $end, $receiptPath, $psid, $workflow) {
            $application = Application::query()->create([
                'application_no' => $workflow->nextApplicationNo(),
                'user_id' => $request->user()->id,
                'reservoir_id' => $reservoir->id,
                'category_id' => $category->id,
                'fishing_start_date' => $data['fishing_start_date'],
                'fishing_end_date' => $end->toDateString(),
                'fee_amount_snapshot' => $category->fee_amount,
                'currency' => $category->currency,
                'status' => Application::STATUS_UNDER_REVIEW,
                'payment_method' => $data['payment_method'],
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

        return redirect()
            ->route('citizen.applications.success', $application)
            ->with('status', 'Application submitted for review.');
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
