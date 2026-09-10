<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClosedSeason;
use App\Models\LicenseCategory;
use App\Models\Module;
use App\Models\Reservoir;
use App\Models\User;
use App\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function index(SystemSettingService $settings): View
    {
        return view('admin.setup.index', [
            'steps' => $this->steps($settings),
            'settings' => $settings->all(),
        ]);
    }

    public function fees(SystemSettingService $settings): View
    {
        return view('admin.setup.fees', [
            'steps' => $this->steps($settings),
            'categories' => LicenseCategory::query()->orderBy('fee_amount')->get(),
            'closedSeason' => ClosedSeason::query()
                ->where('scope_type', 'global')
                ->orderByDesc('is_active')
                ->first(),
            'seasonalExpiry' => $settings->seasonalExpiryMonthDay(),
            'confirmed' => $settings->bool('setup_confirmed_fees'),
        ]);
    }

    public function saveFees(Request $request, SystemSettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'seasonal_expiry_month_day' => ['required', 'regex:/^(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'],
            'fees' => ['required', 'array'],
            'fees.*.id' => ['required', 'integer', 'exists:license_categories,id'],
            'fees.*.fee_amount' => ['required', 'numeric', 'min:0'],
            'fees.*.is_active' => ['sometimes', 'boolean'],
            'closed_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'closed_start_day' => ['required', 'integer', 'min:1', 'max:31'],
            'closed_end_month' => ['required', 'integer', 'min:1', 'max:12'],
            'closed_end_day' => ['required', 'integer', 'min:1', 'max:31'],
            'closed_reason' => ['nullable', 'string', 'max:255'],
            'closed_is_active' => ['sometimes', 'boolean'],
            'confirm' => ['accepted'],
        ]);

        DB::transaction(function () use ($data, $settings, $request) {
            $expiry = $data['seasonal_expiry_month_day'];
            $settings->set('seasonal_expiry_month_day', $expiry);

            foreach ($data['fees'] as $row) {
                $category = LicenseCategory::query()->findOrFail($row['id']);
                $payload = [
                    'fee_amount' => $row['fee_amount'],
                    'is_active' => ! empty($row['is_active']),
                ];

                if ($category->duration_type === 'seasonal'
                    || in_array($category->expiry_rule, ['fixed_date', 'season_end'], true)) {
                    $payload['expiry_rule'] = $category->expiry_rule === 'from_start'
                        ? 'fixed_date'
                        : $category->expiry_rule;
                    $payload['fixed_expiry_month_day'] = $expiry;
                }

                $category->update($payload);
            }

            $closedActive = $request->boolean('closed_is_active');

            ClosedSeason::query()->updateOrCreate(
                [
                    'scope_type' => 'global',
                    'scope_id' => null,
                    'start_month' => (int) $data['closed_start_month'],
                    'start_day' => (int) $data['closed_start_day'],
                    'end_month' => (int) $data['closed_end_month'],
                    'end_day' => (int) $data['closed_end_day'],
                ],
                [
                    'reason' => $data['closed_reason'] ?: 'Breeding / closed season',
                    'is_active' => $closedActive,
                ]
            );

            if ($closedActive) {
                ClosedSeason::query()
                    ->where('scope_type', 'global')
                    ->where(function ($q) use ($data) {
                        $q->where('start_month', '!=', (int) $data['closed_start_month'])
                            ->orWhere('start_day', '!=', (int) $data['closed_start_day'])
                            ->orWhere('end_month', '!=', (int) $data['closed_end_month'])
                            ->orWhere('end_day', '!=', (int) $data['closed_end_day']);
                    })
                    ->update(['is_active' => false]);
            }

            $settings->set('setup_confirmed_fees', true);
        });

        return redirect()
            ->route('admin.setup.eligibility')
            ->with('status', 'Step 1 saved: fees, 30 June rule, and closed season.');
    }

    public function eligibility(Request $request, SystemSettingService $settings): View
    {
        $user = $request->user();
        $base = Reservoir::query()->forUserDistricts($user)->where('is_active', true);

        $counts = [
            'open' => (clone $base)->where('is_open_for_licensing', true)->count(),
            'closed' => (clone $base)->where('is_open_for_licensing', false)->count(),
            'undecided' => (clone $base)->whereNull('is_open_for_licensing')->count(),
            'total' => (clone $base)->count(),
        ];

        $reservoirs = Reservoir::query()
            ->with('district')
            ->forUserDistricts($user)
            ->where('is_active', true)
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->integer('district_id')))
            ->when($request->filled('eligibility'), function ($q) use ($request) {
                return match ($request->string('eligibility')->toString()) {
                    'open' => $q->where('is_open_for_licensing', true),
                    'closed' => $q->where('is_open_for_licensing', false),
                    'undecided' => $q->whereNull('is_open_for_licensing'),
                    default => $q,
                };
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where('name', 'ilike', $term);
            })
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.setup.eligibility', [
            'steps' => $this->steps($settings),
            'reservoirs' => $reservoirs,
            'counts' => $counts,
            'requireExplicit' => $settings->requireExplicitELicence(),
            'confirmed' => $settings->bool('setup_confirmed_eligibility'),
            'districts' => \App\Models\District::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function saveEligibility(Request $request, SystemSettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'require_explicit_e_licence' => ['sometimes', 'boolean'],
            'eligibility' => ['nullable', 'array'],
            'eligibility.*' => ['nullable', Rule::in(['1', '0', ''])],
            'confirm' => ['accepted'],
        ]);

        $user = $request->user();
        $settings->set('require_explicit_e_licence', $request->boolean('require_explicit_e_licence'));

        foreach ($data['eligibility'] ?? [] as $id => $value) {
            $reservoir = Reservoir::query()->forUserDistricts($user)->find((int) $id);
            if (! $reservoir) {
                continue;
            }

            $reservoir->update([
                'is_open_for_licensing' => $value === '' || $value === null
                    ? null
                    : ($value === '1'),
            ]);
        }

        $openCount = Reservoir::query()
            ->forUserDistricts($user)
            ->where('is_active', true)
            ->where('is_open_for_licensing', true)
            ->count();

        if ($openCount < 1) {
            return back()->withErrors([
                'eligibility' => 'Mark at least one water body as e-licence eligible before confirming.',
            ])->withInput();
        }

        $settings->set('setup_confirmed_eligibility', true);

        return redirect()
            ->route('admin.setup.templates')
            ->with('status', "Step 2 saved: {$openCount} water body(ies) open for e-licence.");
    }

    public function templates(SystemSettingService $settings): View
    {
        $templates = config('rflms.permission_templates', []);
        $modules = Module::query()->where('is_active', true)->orderBy('sort_order')->get()->keyBy('key');

        return view('admin.setup.templates', [
            'steps' => $this->steps($settings),
            'templates' => $templates,
            'modules' => $modules,
            'staffCount' => User::query()->where('user_type', User::TYPE_ADMIN)->where('is_active', true)->count(),
            'confirmed' => $settings->bool('setup_confirmed_templates'),
        ]);
    }

    public function saveTemplates(Request $request, SystemSettingService $settings): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']]);
        $settings->set('setup_confirmed_templates', true);

        return redirect()
            ->route('admin.setup.qr')
            ->with('status', 'Step 3 confirmed: use templates when creating staff users.');
    }

    public function qr(SystemSettingService $settings): View
    {
        return view('admin.setup.qr', [
            'steps' => $this->steps($settings),
            'qrPrivacy' => $settings->qrPrivacy(),
            'showCnic' => $settings->bool('show_cnic_on_public_qr', false),
            'confirmed' => $settings->bool('setup_confirmed_qr'),
            'sampleName' => 'Muhammad Ali Khan',
            'sampleCnic' => '17301-1234567-1',
        ]);
    }

    public function saveQr(Request $request, SystemSettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'qr_privacy' => ['required', Rule::in(['full', 'masked'])],
            'show_cnic_on_public_qr' => ['sometimes', 'boolean'],
            'confirm' => ['accepted'],
        ]);

        $settings->setMany([
            'qr_privacy' => $data['qr_privacy'],
            'show_cnic_on_public_qr' => $request->boolean('show_cnic_on_public_qr'),
            'setup_confirmed_qr' => true,
        ]);

        return redirect()
            ->route('admin.setup.index')
            ->with('status', 'Step 4 saved: public QR privacy updated. Setup complete.');
    }

    /**
     * @return list<array{key: string, label: string, route: string, done: bool, ready: bool, hint: string}>
     */
    private function steps(SystemSettingService $settings): array
    {
        $activeCategories = LicenseCategory::query()->where('is_active', true)->count();
        $seasonalOk = LicenseCategory::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('duration_type', 'seasonal')
                    ->orWhereIn('expiry_rule', ['fixed_date', 'season_end']);
            })
            ->where('fixed_expiry_month_day', $settings->seasonalExpiryMonthDay())
            ->exists();
        $closedOk = ClosedSeason::query()->where('scope_type', 'global')->where('is_active', true)->exists();
        $openWaters = Reservoir::query()->where('is_active', true)->where('is_open_for_licensing', true)->count();

        return [
            [
                'key' => 'fees',
                'label' => '1. Fees & 30 June rule',
                'route' => 'admin.setup.fees',
                'done' => $settings->bool('setup_confirmed_fees'),
                'ready' => $activeCategories > 0 && $seasonalOk && $closedOk,
                'hint' => 'Set Daily/Monthly/Seasonal fees, seasonal expiry, closed months',
            ],
            [
                'key' => 'eligibility',
                'label' => '2. E-licence waters',
                'route' => 'admin.setup.eligibility',
                'done' => $settings->bool('setup_confirmed_eligibility'),
                'ready' => $openWaters > 0,
                'hint' => 'Mark which water bodies accept individual e-licences',
            ],
            [
                'key' => 'templates',
                'label' => '3. Staff permission templates',
                'route' => 'admin.setup.templates',
                'done' => $settings->bool('setup_confirmed_templates'),
                'ready' => true,
                'hint' => 'DG / District / Office / Field presets for User Management',
            ],
            [
                'key' => 'qr',
                'label' => '4. Public QR privacy',
                'route' => 'admin.setup.qr',
                'done' => $settings->bool('setup_confirmed_qr'),
                'ready' => true,
                'hint' => 'Full vs masked holder name / CNIC on public verify',
            ],
        ];
    }
}
