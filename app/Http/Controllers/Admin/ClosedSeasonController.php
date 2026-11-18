<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClosedSeason;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClosedSeasonController extends Controller
{
    public function index(Request $request): View
    {
        $query = ClosedSeason::query()
            ->with(['reservoir.district', 'category']);

        if ($request->filled('scope')) {
            $query->where('scope_type', $request->string('scope'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'active');
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('reason', 'ilike', $term)
                    ->orWhereHas('reservoir', fn ($rq) => $rq->where('name', 'ilike', $term))
                    ->orWhereHas('category', fn ($cq) => $cq->where('name', 'ilike', $term));
            });
        }

        $seasons = (clone $query)
            ->orderByRaw("CASE WHEN scope_type = 'global' THEN 1 WHEN scope_type = 'reservoir' THEN 2 ELSE 3 END")
            ->orderBy('start_month')
            ->orderBy('start_day')
            ->paginate(15)
            ->withQueryString();

        $allSeasons = ClosedSeason::query()->with(['reservoir', 'category'])->get();
        $totalCount = $allSeasons->count();
        $activeCount = $allSeasons->where('is_active', true)->count();
        $activeTodaySeasons = $allSeasons->filter(fn (ClosedSeason $s) => $s->isCurrentlyActive());

        return view('admin.closed-seasons.index', [
            'seasons' => $seasons,
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'activeTodaySeasons' => $activeTodaySeasons,
        ]);
    }

    public function create(Request $request): View
    {
        $season = new ClosedSeason([
            'scope_type' => $request->string('scope_type', 'global'),
            'scope_id' => $request->filled('scope_id') ? $request->integer('scope_id') : null,
            'start_month' => 6,
            'start_day' => 1,
            'end_month' => 7,
            'end_day' => 31,
            'reason' => 'Statutory breeding / closed season',
            'is_active' => true,
        ]);

        return view('admin.closed-seasons.form', $this->formData($season, 'create'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        ClosedSeason::query()->create($data);

        return redirect()
            ->route('admin.closed-seasons.index')
            ->with('status', 'Closed season created successfully.');
    }

    public function edit(ClosedSeason $closedSeason): View
    {
        return view('admin.closed-seasons.form', $this->formData($closedSeason, 'edit'));
    }

    public function update(Request $request, ClosedSeason $closedSeason): RedirectResponse
    {
        $data = $this->validated($request, $closedSeason);

        $closedSeason->update($data);

        return redirect()
            ->route('admin.closed-seasons.index')
            ->with('status', 'Closed season updated successfully.');
    }

    public function toggle(ClosedSeason $closedSeason): RedirectResponse
    {
        $closedSeason->update([
            'is_active' => ! $closedSeason->is_active,
        ]);

        $state = $closedSeason->is_active ? 'activated' : 'deactivated';

        return back()->with('status', "Closed season #{$closedSeason->id} has been {$state}.");
    }

    public function destroy(ClosedSeason $closedSeason): RedirectResponse
    {
        $id = $closedSeason->id;
        $closedSeason->delete();

        return redirect()
            ->route('admin.closed-seasons.index')
            ->with('status', "Closed season #{$id} deleted successfully.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ClosedSeason $season = null): array
    {
        $data = $request->validate([
            'scope_type' => ['required', Rule::in(['global', 'reservoir', 'category'])],
            'scope_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('scope_type'), ['reservoir', 'category'], true)),
            ],
            'start_month' => ['required', 'integer', 'between:1,12'],
            'start_day' => ['required', 'integer', 'between:1,31'],
            'end_month' => ['required', 'integer', 'between:1,12'],
            'end_day' => ['required', 'integer', 'between:1,31'],
            'reason' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data['scope_type'] === 'reservoir') {
            if (! Reservoir::query()->where('id', $data['scope_id'])->exists()) {
                throw ValidationException::withMessages([
                    'scope_id' => 'The selected water body does not exist.',
                ]);
            }
        } elseif ($data['scope_type'] === 'category') {
            if (! LicenseCategory::query()->where('id', $data['scope_id'])->exists()) {
                throw ValidationException::withMessages([
                    'scope_id' => 'The selected licence category does not exist.',
                ]);
            }
        } else {
            $data['scope_id'] = null;
        }

        // Validate that calendar dates exist (using 2024 to accept Feb 29)
        if (! checkdate((int) $data['start_month'], (int) $data['start_day'], 2024)) {
            throw ValidationException::withMessages([
                'start_day' => 'The start day is invalid for the selected month.',
            ]);
        }
        if (! checkdate((int) $data['end_month'], (int) $data['end_day'], 2024)) {
            throw ValidationException::withMessages([
                'end_day' => 'The end day is invalid for the selected month.',
            ]);
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['reason'] = $data['reason'] ?: 'Breeding / closed season';

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(ClosedSeason $season, string $mode): array
    {
        $reservoirs = Reservoir::query()
            ->with('district')
            ->orderBy('name')
            ->get();

        $categories = LicenseCategory::query()
            ->orderBy('name')
            ->get();

        return [
            'season' => $season,
            'reservoirs' => $reservoirs,
            'categories' => $categories,
            'months' => ClosedSeason::monthOptions(),
            'mode' => $mode,
        ];
    }
}

