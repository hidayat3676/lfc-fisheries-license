<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CitizenProfile;
use App\Models\License;
use App\Models\Office;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use App\Models\User;
use App\Models\ViolationReport;
use App\Services\ApplicationWorkflowService;
use App\Services\LicensingPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OfficerController extends Controller
{
    public function searchByCnic(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdminStaff()) {
            return response()->json(['message' => 'Staff access required'], 403);
        }

        $cnic = $request->input('cnic');
        if (blank($cnic)) {
            return response()->json([
                'status' => 'error',
                'message' => 'CNIC is required for search.',
            ], 422);
        }

        $profile = CitizenProfile::findByCnic((string) $cnic);

        if (! $profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'No record found for the provided CNIC.',
            ], 404);
        }

        $profile->load(['residenceDistrict', 'user']);

        $citizenUser = $profile->user;
        $licenses = [];
        $applications = [];

        if ($citizenUser) {
            $licenses = License::query()
                ->where('user_id', $citizenUser->id)
                ->with(['reservoir.district', 'category'])
                ->latest()
                ->get();

            $applications = Application::query()
                ->where('user_id', $citizenUser->id)
                ->with(['reservoir.district', 'category', 'appliedBy'])
                ->latest()
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'profile' => $profile,
                'user' => $citizenUser ? [
                    'id' => $citizenUser->id,
                    'name' => $citizenUser->name,
                    'email' => $citizenUser->email,
                    'mobile' => $citizenUser->mobile,
                    'is_active' => $citizenUser->is_active,
                ] : null,
                'licenses' => $licenses,
                'applications' => $applications,
            ],
        ]);
    }

    public function walkinRegistration(Request $request): JsonResponse
    {
        $staffUser = $request->user();
        if (! $staffUser->isAdminStaff()) {
            return response()->json(['message' => 'Staff access required'], 403);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'full_name' => ['required', 'string', 'max:120'],
            'father_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'mobile' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cnic' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d$/', 'unique:citizen_profiles,cnic'],
            'dob' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'address' => ['required', 'string', 'max:255'],
            'residence_district_id' => ['required', 'integer', 'exists:districts,id'],
            'province' => ['required', 'string', 'max:80'],
            'emergency_contact' => ['required', 'string', 'max:80'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ], [
            'cnic.regex' => 'CNIC must be in format 12345-1234567-1.',
            'cnic.unique' => 'This CNIC is already registered.',
            'email.unique' => 'This email address is already registered.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profiles', 'public');
        }

        $user = User::query()->create([
            'name' => $data['name'] ?? $data['full_name'],
            'email' => strtolower($data['email']),
            'mobile' => $data['mobile'],
            'password' => $data['password'],
            'user_type' => User::TYPE_CITIZEN,
            'email_verified_at' => now(),
            'is_active' => true,
            'all_districts' => false,
        ]);

        $profile = $user->citizenProfile()->create([
            'full_name' => $data['full_name'],
            'father_name' => $data['father_name'],
            'cnic' => $data['cnic'],
            'dob' => $data['dob'],
            'gender' => $data['gender'],
            'address' => $data['address'],
            'residence_district_id' => $data['residence_district_id'],
            'province' => $data['province'],
            'emergency_contact' => $data['emergency_contact'],
            'photo_path' => $photoPath,
        ]);

        $profile->load('residenceDistrict');

        return response()->json([
            'status' => 'success',
            'message' => 'Walk-in citizen registered and profile completed successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'user_type' => $user->user_type,
                    'email_verified_at' => $user->email_verified_at,
                    'is_active' => $user->is_active,
                    'profile_complete' => $user->hasCompleteCitizenProfile(),
                ],
                'profile' => $profile,
            ],
        ], 201);
    }

    public function walkinApplication(
        Request $request,
        LicensingPolicyService $policy,
        ApplicationWorkflowService $workflow
    ): JsonResponse {
        $staffUser = $request->user();
        if (! $staffUser->isAdminStaff()) {
            return response()->json(['message' => 'Staff access required'], 403);
        }

        $data = $request->validate([
            'cnic' => ['required', 'string'],
            'reservoir_id' => ['required', 'integer', 'exists:reservoirs,id'],
            'category_id' => ['required', 'integer', Rule::exists('license_categories', 'id')->where('is_active', true)],
            'fishing_start_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', Rule::in(Application::paymentMethods())],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'auto_approve' => ['nullable', 'boolean'],
            'officer_remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'cnic.required' => 'CNIC is required.',
            'reservoir_id.exists' => 'Selected reservoir does not exist.',
            'category_id.exists' => 'Selected license category is invalid or inactive.',
        ]);

        $profile = CitizenProfile::findByCnic((string) $data['cnic']);
        if (! $profile || ! $profile->user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No user record found for the provided CNIC. Please register citizen first.',
            ], 404);
        }

        $citizenUser = $profile->user;
        if (! $citizenUser->hasCompleteCitizenProfile()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Citizen profile is incomplete. Complete profile before submitting application.',
                'code' => 'PROFILE_INCOMPLETE',
            ], 422);
        }

        $method = $data['payment_method'] === Application::PAYMENT_METHOD_ONLINE_TRANSFER
            ? Application::PAYMENT_METHOD_BANK_TRANSFER
            : ($data['payment_method'] === Application::PAYMENT_METHOD_CASH ? Application::PAYMENT_METHOD_COUNTER_CASH : $data['payment_method']);

        if (in_array($method, [Application::PAYMENT_METHOD_BANK_TRANSFER, Application::PAYMENT_METHOD_BANK_DEPOSIT], true)) {
            $request->validate([
                'payment_reference' => ['required', 'string', 'max:120'],
            ]);
        }

        $reservoir = Reservoir::query()->where('is_active', true)->findOrFail($data['reservoir_id']);

        $ids = $staffUser->scopedDistrictIds();
        if ($ids !== null && ! in_array((int) $reservoir->district_id, $ids, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Water body is outside your assigned district scope.',
            ], 403);
        }

        if (! $reservoir->allowsELicence()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Water body is not open for licensing.',
                'code' => 'NOT_ELIGIBLE',
            ], 422);
        }

        if ($policy->hasValidLicense($citizenUser->id, $reservoir->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'User already has a valid active licence for this water body. Re-applying is not allowed until current licence expires.',
                'code' => 'ACTIVE_LICENSE_EXISTS',
            ], 422);
        }

        $category = LicenseCategory::query()->findOrFail($data['category_id']);
        $closed = $policy->isClosedOn($data['fishing_start_date'], $reservoir->id, $category->id);
        if ($closed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fishing closed on this date: '.$closed->reason,
                'code' => 'CLOSED_SEASON',
            ], 422);
        }

        $end = $policy->calculateEndDate($category, $data['fishing_start_date']);
        $receiptPath = $request->hasFile('payment_receipt')
            ? $request->file('payment_receipt')->store('receipts', 'public')
            : null;

        $psid = $method === Application::PAYMENT_METHOD_1BILL
            ? 'PSID-WALKIN-'.strtoupper(substr(md5($citizenUser->id.'|'.$reservoir->id.'|'.now()->timestamp), 0, 10))
            : null;

        $paymentRef = $data['payment_reference'] ?? null;
        if (empty($paymentRef) && $method === Application::PAYMENT_METHOD_COUNTER_CASH) {
            $paymentRef = 'COUNTER-CASH-'.now()->format('YmdHis');
        }

        $application = DB::transaction(function () use ($citizenUser, $staffUser, $reservoir, $category, $data, $end, $receiptPath, $psid, $paymentRef, $workflow, $method) {
            $application = Application::query()->create([
                'application_no' => $workflow->nextApplicationNo(),
                'user_id' => $citizenUser->id,
                'applied_by' => $staffUser->id,
                'reservoir_id' => $reservoir->id,
                'category_id' => $category->id,
                'fishing_start_date' => $data['fishing_start_date'],
                'fishing_end_date' => $end->toDateString(),
                'fee_amount_snapshot' => $category->fee_amount,
                'currency' => $category->currency,
                'status' => Application::STATUS_UNDER_REVIEW,
                'payment_method' => $method,
                'payment_reference' => $paymentRef,
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

        $autoApprove = $request->boolean('auto_approve') || $method === Application::PAYMENT_METHOD_COUNTER_CASH;
        $license = null;

        if ($autoApprove) {
            $remarks = $data['officer_remarks'] ?? 'Walk-in application registered and processed by officer.';
            $license = $workflow->approve($application, $staffUser, $remarks);
        } else {
            $workflow->notifySubmitted($application);
        }

        return response()->json([
            'status' => 'success',
            'message' => $autoApprove
                ? 'Walk-in license application submitted and approved immediately.'
                : 'Walk-in license application submitted for review.',
            'data' => [
                'application' => $application->fresh(['reservoir.district', 'category', 'license', 'appliedBy']),
                'license' => $license,
            ],
        ], 201);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $assignedOfficesCount = Office::query()->where('is_active', true)->count();
        } else {
            $assignedCount = $user->offices()->where('is_active', true)->count();
            if ($assignedCount > 0) {
                $assignedOfficesCount = $assignedCount;
            } elseif ($user->all_districts) {
                $assignedOfficesCount = Office::query()->where('is_active', true)->count();
            } else {
                $assignedOfficesCount = Office::query()->where('is_active', true)->forUserDistricts($user)->count();
            }
        }

        $licensesQuery = License::query()->forUserDistricts($user);
        $totalLicensesIssued = (clone $licensesQuery)->count();
        $activeLicensesCount = (clone $licensesQuery)->where('status', License::STATUS_ACTIVE)->count();

        $pendingApplicationsCount = Application::query()
            ->forUserDistricts($user)
            ->where('status', 'submitted')
            ->count();

        $violationsReportedCount = ViolationReport::query()
            ->forUserDistricts($user)
            ->count();

        $staffReportedViolationsCount = ViolationReport::query()
            ->where('reporter_user_id', $user->id)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'assigned_offices_count' => $assignedOfficesCount,
                'total_licenses_issued' => $totalLicensesIssued,
                'active_licenses_count' => $activeLicensesCount,
                'pending_applications_count' => $pendingApplicationsCount,
                'violations_reported_count' => $violationsReportedCount,
                'staff_reported_violations_count' => $staffReportedViolationsCount,
            ],
        ]);
    }
    public function offices(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $offices = Office::query()
                ->where('is_active', true)
                ->with('district')
                ->withCount('licenses')
                ->orderBy('name')
                ->get();
        } else {
            $assignedOffices = $user->offices()
                ->where('is_active', true)
                ->with('district')
                ->withCount('licenses')
                ->orderBy('name')
                ->get();

            if ($assignedOffices->isNotEmpty()) {
                $offices = $assignedOffices;
            } elseif ($user->all_districts) {
                $offices = Office::query()
                    ->where('is_active', true)
                    ->with('district')
                    ->withCount('licenses')
                    ->orderBy('name')
                    ->get();
            } else {
                $offices = Office::query()
                    ->where('is_active', true)
                    ->forUserDistricts($user)
                    ->with('district')
                    ->withCount('licenses')
                    ->orderBy('name')
                    ->get();
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $offices,
        ]);
    }

    public function officeApplications(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdminStaff()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $accessibleOfficeIds = $this->getAccessibleOfficeIds($user);

        $query = Application::query()
            ->with([
                'user:id,name,email,mobile',
                'user.citizenProfile:id,user_id,full_name,father_name,cnic',
                'reservoir:id,name,district_id,office_id',
                'reservoir.district:id,name',
                'reservoir.office:id,name,district_id',
                'category:id,name,code,fee_amount,currency',
                'license:id,application_id,license_no,status,issue_date,expiry_date,qr_token',
                'appliedBy:id,name,email',
            ]);

        if ($accessibleOfficeIds !== null) {
            

            $accessibleDistrictIds = Office::query()
                ->whereIn('id', $accessibleOfficeIds)
                ->pluck('district_id')
                ->all();

            $query->whereHas('reservoir', function ($q) use ($accessibleOfficeIds, $accessibleDistrictIds) {
                $q->whereIn('office_id', $accessibleOfficeIds)
                    ->orWhere(function ($sub) use ($accessibleDistrictIds) {
                        $sub->whereNull('office_id')
                            ->whereIn('district_id', $accessibleDistrictIds);
                    });
            });
        }

        if ($request->filled('office_id')) {
            $officeId = (int) $request->integer('office_id');

            if ($accessibleOfficeIds !== null && ! in_array($officeId, $accessibleOfficeIds, true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Office is outside your assigned scope.',
                ], 403);
            }

            $office = Office::find($officeId);
            if (! $office) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Office not found.',
                ], 404);
            }

            $query->whereHas('reservoir', function ($q) use ($office) {
                $q->where('office_id', $office->id)
                    ->orWhere(function ($sub) use ($office) {
                        $sub->whereNull('office_id')
                            ->where('district_id', $office->district_id);
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search') || $request->filled('q')) {
            $term = '%'.($request->input('search') ?? $request->input('q')).'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('application_no', 'like', $term)
                    ->orWhereHas('user.citizenProfile', fn ($profile) => $profile->where('cnic', 'like', $term)
                        ->orWhere('full_name', 'like', $term)
                    )
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
        }

        $applications = $query->latest()->paginate((int) $request->integer('per_page', 20));

        return response()->json($applications);
    }

    public function singleOfficeApplications(Request $request, Office $office): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdminStaff()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $accessibleOfficeIds = $this->getAccessibleOfficeIds($user);

        if ($accessibleOfficeIds !== null && ! in_array((int) $office->id, $accessibleOfficeIds, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Office is outside your assigned scope.',
            ], 403);
        }

        $query = Application::query()
            ->with([
                'user:id,name,email,mobile',
                'user.citizenProfile:id,user_id,full_name,father_name,cnic',
                'reservoir:id,name,district_id,office_id',
                'reservoir.district:id,name',
                'reservoir.office:id,name,district_id',
                'category:id,name,code,fee_amount,currency',
                'license:id,application_id,license_no,status,issue_date,expiry_date,qr_token',
                'appliedBy:id,name,email',
            ])
            ->whereHas('reservoir', function ($q) use ($office) {
                $q->where('office_id', $office->id)
                    ->orWhere(function ($sub) use ($office) {
                        $sub->whereNull('office_id')
                            ->where('district_id', $office->district_id);
                    });
            });

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search') || $request->filled('q')) {
            $term = '%'.($request->input('search') ?? $request->input('q')).'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('application_no', 'like', $term)
                    ->orWhereHas('user.citizenProfile', fn ($profile) => $profile->where('cnic', 'like', $term)
                        ->orWhere('full_name', 'like', $term)
                    )
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
        }

        $applications = $query->latest()->paginate((int) $request->integer('per_page', 20));

        return response()->json($applications);
    }

    private function getAccessibleOfficeIds(User $user): ?array
    {
        if ($user->isSuperAdmin()) {
            return null;
        }

        $assignedOffices = $user->offices()
            ->where('is_active', true)
            ->pluck('offices.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! empty($assignedOffices)) {
            return $assignedOffices;
        }

        if ($user->all_districts) {
            return null;
        }

        $scopedDistricts = $user->scopedDistrictIds();
        if ($scopedDistricts === null) {
            return null;
        }

        return Office::query()
            ->where('is_active', true)
            ->whereIn('district_id', $scopedDistricts)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
    
    public function verifyQr(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdminStaff()) {
            return response()->json(['message' => 'Staff access required'], 403);
        }

        $data = $request->validate([
            'token' => ['nullable', 'string'],
            'license_no' => ['nullable', 'string'],
        ]);

        if (empty($data['token']) && empty($data['license_no'])) {
            return response()->json(['message' => 'Provide token or license_no'], 422);
        }

        $license = License::query()
            ->with(['user', 'reservoir.district', 'category'])
            ->when(! empty($data['token']), fn ($q) => $q->where('qr_token', $data['token']))
            ->when(! empty($data['license_no']), fn ($q) => $q->where('license_no', $data['license_no']))
            ->firstOrFail();

        $ids = $user->scopedDistrictIds();
        if ($ids !== null && ! in_array((int) $license->reservoir->district_id, $ids, true)) {
            return response()->json(['message' => 'Outside district scope'], 403);
        }

        $valid = $license->status === License::STATUS_ACTIVE
            && $license->expiry_date->endOfDay()->isFuture();

        return response()->json([
            'status' => $valid ? 'Valid' : ($license->status === License::STATUS_CANCELLED ? 'Cancelled' : 'Expired'),
            'valid' => $valid,
            'license' => $license,
        ]);
    }

    public function violations(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdminStaff() || ! $user->hasModuleAction('violations', 'view')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $reports = ViolationReport::query()
            ->with(['district', 'reservoir'])
            ->forUserDistricts($user)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($reports);
    }

    public function updateViolation(Request $request, ViolationReport $violation): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdminStaff() || ! $user->hasModuleAction('violations', 'status')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ids = $user->scopedDistrictIds();
        if ($ids !== null && ! in_array((int) $violation->district_id, $ids, true)) {
            return response()->json(['message' => 'Outside district scope'], 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in([
                ViolationReport::STATUS_PENDING,
                ViolationReport::STATUS_UNDER_INVESTIGATION,
                ViolationReport::STATUS_RESOLVED,
            ])],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $violation->update([
            'status' => $data['status'],
            'resolution_notes' => $data['resolution_notes'] ?? $violation->resolution_notes,
            'assigned_to' => $user->id,
        ]);

        return response()->json(['message' => 'Updated', 'data' => $violation->fresh()]);
    }

    public function applications(Request $request): JsonResponse
    {
        $staffUser = $request->user();
        if (! $staffUser->isAdminStaff()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $applications = Application::query()
            ->with([
                'user:id,name,email,mobile',
                'user.citizenProfile:id,user_id,full_name,father_name,cnic',
                'reservoir:id,name,district_id',
                'reservoir.district:id,name',
                'category:id,name,code,fee_amount,currency',
                'license:id,application_id,license_no,status,issue_date,expiry_date,qr_token',
                'appliedBy:id,name,email',
            ])
            ->where('applied_by', $staffUser->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('application_no', 'like', $term)
                    ->orWhereHas('user.citizenProfile', fn ($profile) => $profile->where('cnic', 'like', $term)
                    ->orWhere('full_name', 'like', $term));
                });
            })
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($applications);
    }

    public function approveApplication(
        Request $request,
        Application $application,
        ApplicationWorkflowService $workflow
    ): JsonResponse {
        $staffUser = $request->user();

        if (! $staffUser->isAdminStaff()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $ids = $staffUser->scopedDistrictIds();
        if ($ids !== null && ! in_array((int) $application->reservoir?->district_id, $ids, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application is outside your district scope.',
            ], 403);
        }

        if (! $application->isPendingReview()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application is not pending review.',
            ], 422);
        }

        $data = $request->validate([
            'officer_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $license = $workflow->approve($application, $staffUser, $data['officer_remarks'] ?? null);

        return response()->json([
            'status' => 'success',
            'message' => 'Application approved and license issued successfully.',
            'data' => [
                'application' => $application->fresh(['user.citizenProfile', 'reservoir.district', 'category', 'license', 'appliedBy']),
                'license' => $license->fresh(['user', 'reservoir', 'category']),
            ],
        ]);
    }

    public function rejectApplication(
        Request $request,
        Application $application,
        ApplicationWorkflowService $workflow
    ): JsonResponse {
        $staffUser = $request->user();

        if (! $staffUser->isAdminStaff()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $ids = $staffUser->scopedDistrictIds();
        if ($ids !== null && ! in_array((int) $application->reservoir?->district_id, $ids, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application is outside your district scope.',
            ], 403);
        }

        $data = $request->validate([
            'officer_remarks' => ['required', 'string', 'max:1000'],
        ]);

        $workflow->reject($application, $staffUser, $data['officer_remarks']);

        return response()->json([
            'status' => 'success',
            'message' => 'Application rejected successfully.',
            'data' => [
                'application' => $application->fresh(['user.citizenProfile', 'reservoir.district', 'category', 'license', 'appliedBy']),
            ],
        ]);
    }

    public function requestInfoApplication(
        Request $request,
        Application $application,
        ApplicationWorkflowService $workflow
    ): JsonResponse {
        $staffUser = $request->user();

        if (! $staffUser->isAdminStaff()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $ids = $staffUser->scopedDistrictIds();
        if ($ids !== null && ! in_array((int) $application->reservoir?->district_id, $ids, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application is outside your district scope.',
            ], 403);
        }

        $data = $request->validate([
            'officer_remarks' => ['required', 'string', 'max:1000'],
        ]);

        $workflow->requestInfo($application, $staffUser, $data['officer_remarks']);

        return response()->json([
            'status' => 'success',
            'message' => 'Information requested from citizen.',
            'data' => [
                'application' => $application->fresh(['user.citizenProfile', 'reservoir.district', 'category', 'license', 'appliedBy']),
            ],
        ]);
    }
}
