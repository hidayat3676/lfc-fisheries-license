<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Office;
use App\Models\Reservoir;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservoirController extends Controller
{
    public function index(Request $request): View
    {
        $reservoirs = Reservoir::query()
            ->with(['district', 'office'])
            ->forUserDistricts($request->user())
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->integer('district_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('water_body_type', $request->string('type')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where('name', 'ilike', $term);
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reservoirs.index', [
            'reservoirs' => $reservoirs,
            'districts' => $this->districtOptions($request),
            'types' => config('rflms.water_body_types'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.reservoirs.form', $this->formData($request, new Reservoir([
            'is_active' => true,
            'water_body_type' => 'dam',
            'trout_type' => 'non_trout',
        ]), 'create'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->assertDistrictAllowed($request, (int) $data['district_id']);

        Reservoir::query()->create($data);

        return redirect()->route('admin.reservoirs.index')->with('status', 'Water body created.');
    }

    public function edit(Request $request, Reservoir $reservoir): View
    {
        $this->assertReservoirAllowed($request, $reservoir);

        return view('admin.reservoirs.form', $this->formData($request, $reservoir, 'edit'));
    }

    public function update(Request $request, Reservoir $reservoir): RedirectResponse
    {
        $this->assertReservoirAllowed($request, $reservoir);
        $data = $this->validated($request, $reservoir);
        $this->assertDistrictAllowed($request, (int) $data['district_id']);

        $reservoir->update($data);

        return redirect()->route('admin.reservoirs.index')->with('status', 'Water body updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Reservoir $reservoir = null): array
    {
        $data = $request->validate([
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'office_id' => [
                'nullable',
                'integer',
                Rule::exists('offices', 'id')->where(fn ($q) => $q->where('district_id', $request->integer('district_id'))),
            ],
            'name' => ['required', 'string', 'max:190'],
            'water_body_type' => ['required', Rule::in(array_keys(config('rflms.water_body_types')))],
            'trout_type' => ['required', Rule::in(array_keys(config('rflms.trout_types')))],
            'description' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:30,37'],
            'longitude' => ['nullable', 'numeric', 'between:69,75'],
            'species_notes' => ['nullable', 'string'],
            'lease_notes' => ['nullable', 'string'],
            'is_open_for_licensing' => ['nullable', 'in:1,0'],
            'is_active' => ['sometimes', 'boolean'],
            'needs_review' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('reservoirs', 'public');
            $data['image_path'] = $path;
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['needs_review'] = $request->boolean('needs_review');

        if ($request->filled('is_open_for_licensing')) {
            $data['is_open_for_licensing'] = $request->input('is_open_for_licensing') === '1';
        } else {
            $data['is_open_for_licensing'] = null;
        }

        if (! empty($data['latitude']) && ! empty($data['longitude'])) {
            $data['directions_url'] = 'https://www.google.com/maps?q='.$data['latitude'].','.$data['longitude'];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, Reservoir $reservoir, string $mode): array
    {
        $districts = $this->districtOptions($request);
        $offices = Office::query()
            ->with('district')
            ->active()
            ->forUserDistricts($request->user())
            ->orderBy('name')
            ->get();

        return [
            'reservoir' => $reservoir,
            'districts' => $districts,
            'offices' => $offices,
            'types' => config('rflms.water_body_types'),
            'troutTypes' => config('rflms.trout_types'),
            'mode' => $mode,
        ];
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

    private function assertReservoirAllowed(Request $request, Reservoir $reservoir): void
    {
        $this->assertDistrictAllowed($request, (int) $reservoir->district_id);
    }
}
