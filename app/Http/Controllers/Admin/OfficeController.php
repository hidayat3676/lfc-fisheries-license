<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function index(Request $request): View
    {
        $offices = Office::query()
            ->with('district')
            ->forUserDistricts($request->user())
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->integer('district_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'ilike', $term)
                        ->orWhere('email', 'ilike', $term)
                        ->orWhere('phone', 'ilike', $term);
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.offices.index', [
            'offices' => $offices,
            'districts' => $this->districtOptions($request),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.offices.form', [
            'office' => new Office(['is_active' => true]),
            'districts' => $this->districtOptions($request),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->assertDistrictAllowed($request, (int) $data['district_id']);

        Office::query()->create($data);

        return redirect()->route('admin.offices.index')->with('status', 'Office created.');
    }

    public function edit(Request $request, Office $office): View
    {
        $this->assertOfficeAllowed($request, $office);

        return view('admin.offices.form', [
            'office' => $office,
            'districts' => $this->districtOptions($request),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Office $office): RedirectResponse
    {
        $this->assertOfficeAllowed($request, $office);
        $data = $this->validated($request, $office);
        $this->assertDistrictAllowed($request, (int) $data['district_id']);

        $office->update($data);

        return redirect()->route('admin.offices.index')->with('status', 'Office updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Office $office = null): array
    {
        $data = $request->validate([
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'name' => [
                'required',
                'string',
                'max:190',
                Rule::unique('offices', 'name')
                    ->where(fn ($q) => $q->where('district_id', $request->integer('district_id')))
                    ->ignore($office?->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function districtOptions(Request $request)
    {
        $query = District::query()->where('is_active', true)->orderBy('name');
        $ids = $request->user()->scopedDistrictIds();
        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        return $query->get();
    }

    private function assertDistrictAllowed(Request $request, int $districtId): void
    {
        $ids = $request->user()->scopedDistrictIds();
        if ($ids !== null && ! in_array($districtId, $ids, true)) {
            abort(403, 'District out of scope.');
        }
    }

    private function assertOfficeAllowed(Request $request, Office $office): void
    {
        $this->assertDistrictAllowed($request, (int) $office->district_id);
    }
}
