<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LicenseCategoryController extends Controller
{
    public function index(): View
    {
        $categories = LicenseCategory::query()->orderBy('name')->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new LicenseCategory([
                'currency' => 'PKR',
                'duration_type' => 'daily',
                'duration_days' => 1,
                'expiry_rule' => 'from_start',
                'is_active' => true,
                'fee_amount' => 0,
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LicenseCategory::query()->create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(LicenseCategory $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, LicenseCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?LicenseCategory $category = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('license_categories', 'code')->ignore($category?->id)],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'duration_type' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'seasonal', 'custom'])],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:400'],
            'fee_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'max_fish_limit' => ['nullable', 'integer', 'min:1'],
            'instructions' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'expiry_rule' => ['required', Rule::in(['from_start', 'fixed_date', 'season_end'])],
            'fixed_expiry_month_day' => ['nullable', 'regex:/^(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['code'] = strtoupper($data['code']);
        $data['required_documents'] = ['payment_receipt'];

        if ($data['expiry_rule'] === 'from_start' && empty($data['duration_days'])) {
            $data['duration_days'] = match ($data['duration_type']) {
                'daily' => 1,
                'weekly' => 7,
                'monthly' => 30,
                default => 1,
            };
        }

        if (in_array($data['expiry_rule'], ['fixed_date', 'season_end'], true) && empty($data['fixed_expiry_month_day'])) {
            $data['fixed_expiry_month_day'] = '06-30';
        }

        return $data;
    }
}
