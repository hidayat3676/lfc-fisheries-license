<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CitizenProfile;
use App\Models\District;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use App\Models\User;
use App\Services\ApplicationWorkflowService;
use App\Services\LicensingPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = Application::query()
            ->with(['user', 'reservoir.district', 'category'])
            ->forUserDistricts($request->user())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('application_no', 'ilike', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', $term)->orWhere('email', 'ilike', $term));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.applications.index', compact('applications'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasModuleAction('applications', 'create'), 403);

        $citizens = User::query()
            ->where('user_type', User::TYPE_CITIZEN)
            ->with('citizenProfile')
            ->orderBy('name')
            ->limit(100)
            ->get();

        $reservoirs = Reservoir::query()
            ->where('is_active', true)
            ->forUserDistricts($request->user())
            ->with(['district', 'office'])
            ->orderBy('name')
            ->get();

        $categories = LicenseCategory::query()
            ->active()
            ->orderBy('fee_amount')
            ->get();

        $districts = District::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.applications.create', [
            'citizens' => $citizens,
            'reservoirs' => $reservoirs,
            'categories' => $categories,
            'districts' => $districts,
            'paymentMethods' => [
                'counter_cash' => 'Counter Cash Payment (Instant Issue)',
                'bank_transfer' => 'Online Bank Transfer',
                'bank_deposit' => 'Bank Deposit Slip',
                '1bill' => '1Bill / PSID System',
            ],
        ]);
    }

    public function store(
        Request $request,
        LicensingPolicyService $policy,
        ApplicationWorkflowService $workflow
    ): RedirectResponse {
        abort_unless($request->user()->hasModuleAction('applications', 'create'), 403);

        $data = $request->validate([
            'citizen_type' => ['required', Rule::in(['existing', 'new'])],
            'user_id' => ['nullable', 'required_if:citizen_type,existing', 'exists:users,id'],

            // New citizen profile fields
            'full_name' => ['nullable', 'required_if:citizen_type,new', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'password' => ['nullable', 'string', 'min:8'],
            'cnic' => ['nullable', 'required_if:citizen_type,new', 'regex:/^\d{5}-\d{7}-\d$/'],
            'father_name' => ['nullable', 'required_if:citizen_type,new', 'string', 'max:120'],
            'mobile' => ['nullable', 'required_if:citizen_type,new', 'string', 'max:30'],
            'dob' => ['nullable', 'required_if:citizen_type,new', 'date', 'before:today'],
            'gender' => ['nullable', 'required_if:citizen_type,new', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'required_if:citizen_type,new', 'string', 'max:255'],
            'residence_district_id' => ['nullable', 'required_if:citizen_type,new', 'exists:districts,id'],
            'province' => ['nullable', 'required_if:citizen_type,new', 'string', 'max:80'],
            'emergency_contact' => ['nullable', 'required_if:citizen_type,new', 'string', 'max:80'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],

            // License application fields
            'reservoir_id' => ['required', 'integer', 'exists:reservoirs,id'],
            'category_id' => ['required', 'integer', Rule::exists('license_categories', 'id')->where('is_active', true)],
            'fishing_start_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', Rule::in(['counter_cash', 'bank_transfer', 'bank_deposit', '1bill'])],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'auto_approve' => ['nullable', 'boolean'],
            'officer_remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'cnic.regex' => 'CNIC must be in format 12345-1234567-1.',
        ]);

        $reservoir = Reservoir::query()->where('is_active', true)->findOrFail($data['reservoir_id']);
        $this->assertScopedDistrict($request, (int) $reservoir->district_id);

        $category = LicenseCategory::query()->findOrFail($data['category_id']);
        $start = $data['fishing_start_date'];

        $closed = $policy->isClosedOn($start, $reservoir->id, $category->id);
        if ($closed) {
            throw ValidationException::withMessages([
                'fishing_start_date' => 'Fishing is not allowed on this date ('.$closed->reason.').',
            ]);
        }

        $end = $policy->calculateEndDate($category, $start);

        $application = DB::transaction(function () use ($request, $data, $reservoir, $category, $end, $workflow) {
            if ($data['citizen_type'] === 'existing') {
                $user = User::query()->findOrFail($data['user_id']);
            } else {
                $cnicClean = preg_replace('/\D/', '', $data['cnic']);
                $email = $data['email'] ?: 'walkin_'.$cnicClean.'@fisheries.kp.gov.pk';

                $user = User::query()->where('email', $email)->first();
                if (! $user) {
                    $user = User::query()->create([
                        'name' => $data['full_name'],
                        'email' => strtolower($email),
                        'mobile' => $data['mobile'],
                        'password' => ! empty($data['password']) ? $data['password'] : Str::random(16),
                        'user_type' => User::TYPE_CITIZEN,
                        'email_verified_at' => now(),
                        'is_active' => true,
                        'all_districts' => false,
                    ]);
                } elseif (! empty($data['password'])) {
                    $user->password = $data['password'];
                    $user->save();
                }

                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $photoPath = $request->file('photo')->store('profiles', 'public');
                }

                $user->citizenProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'full_name' => $data['full_name'],
                        'father_name' => $data['father_name'],
                        'cnic' => $data['cnic'],
                        'dob' => $data['dob'],
                        'gender' => $data['gender'],
                        'address' => $data['address'],
                        'residence_district_id' => $data['residence_district_id'],
                        'province' => $data['province'] ?? 'Khyber Pakhtunkhwa',
                        'emergency_contact' => $data['emergency_contact'],
                        'photo_path' => $photoPath ?? $user->citizenProfile?->photo_path,
                    ]
                );
            }

            $receiptPath = null;
            if ($request->hasFile('payment_receipt')) {
                $receiptPath = $request->file('payment_receipt')->store('receipts', 'public');
            }

            $psid = $data['payment_method'] === '1bill'
                ? 'PSID-WALKIN-'.strtoupper(substr(md5($user->id.'|'.$reservoir->id.'|'.now()->timestamp), 0, 10))
                : null;

            $application = Application::query()->create([
                'application_no' => $workflow->nextApplicationNo(),
                'user_id' => $user->id,
                'applied_by' => $request->user()->id,
                'reservoir_id' => $reservoir->id,
                'category_id' => $category->id,
                'fishing_start_date' => $data['fishing_start_date'],
                'fishing_end_date' => $end->toDateString(),
                'fee_amount_snapshot' => $category->fee_amount,
                'currency' => $category->currency,
                'status' => Application::STATUS_UNDER_REVIEW,
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? ($data['payment_method'] === 'counter_cash' ? 'COUNTER-CASH-'.now()->format('YmdHis') : null),
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

        if (! empty($data['auto_approve']) || $data['payment_method'] === 'counter_cash') {
            $remarks = $data['officer_remarks'] ?: 'Walk-in application registered and counter payment received.';
            $license = $workflow->approve($application, $request->user(), $remarks);

            return redirect()
                ->route('admin.applications.show', $application)
                ->with('status', 'Walk-in licence issued immediately. Licence No: '.$license->license_no);
        }

        $workflow->notifySubmitted($application);

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', 'Walk-in application registered for review.');
    }

    public function show(Request $request, Application $application): View
    {
        $this->assertScoped($request, $application);
        $application->load(['user.citizenProfile', 'reservoir.district', 'reservoir.office', 'category', 'license', 'payments', 'reviewer']);

        return view('admin.applications.show', compact('application'));
    }

    public function approve(Request $request, Application $application, ApplicationWorkflowService $workflow): RedirectResponse
    {
        abort_unless($request->user()->hasModuleAction('applications', 'approve'), 403);
        $this->assertScoped($request, $application);

        if (! $application->isPendingReview()) {
            return back()->withErrors(['status' => 'Application is not pending review.']);
        }

        $data = $request->validate([
            'officer_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $license = $workflow->approve($application, $request->user(), $data['officer_remarks'] ?? null);

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', 'Approved. Licence '.$license->license_no.' issued.');
    }

    public function reject(Request $request, Application $application, ApplicationWorkflowService $workflow): RedirectResponse
    {
        abort_unless($request->user()->hasModuleAction('applications', 'approve'), 403);
        $this->assertScoped($request, $application);

        $data = $request->validate([
            'officer_remarks' => ['required', 'string', 'max:1000'],
        ]);

        $workflow->reject($application, $request->user(), $data['officer_remarks']);

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', 'Application rejected.');
    }

    public function requestInfo(Request $request, Application $application, ApplicationWorkflowService $workflow): RedirectResponse
    {
        abort_unless($request->user()->hasModuleAction('applications', 'approve'), 403);
        $this->assertScoped($request, $application);

        $data = $request->validate([
            'officer_remarks' => ['required', 'string', 'max:1000'],
        ]);

        $workflow->requestInfo($application, $request->user(), $data['officer_remarks']);

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', 'Information requested from citizen.');
    }

    private function assertScoped(Request $request, Application $application): void
    {
        $ids = $request->user()->scopedDistrictIds();
        if ($ids === null) {
            return;
        }

        $districtId = $application->reservoir()->value('district_id');
        if (! in_array((int) $districtId, $ids, true)) {
            abort(403, 'Application outside your district scope.');
        }
    }

    private function assertScopedDistrict(Request $request, int $districtId): void
    {
        $ids = $request->user()->scopedDistrictIds();
        if ($ids === null) {
            return;
        }

        if (! in_array($districtId, $ids, true)) {
            abort(403, 'Water body is outside your district scope.');
        }
    }
}
