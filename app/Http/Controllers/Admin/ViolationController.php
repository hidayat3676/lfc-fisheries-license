<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ViolationReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ViolationController extends Controller
{
    public function index(Request $request): View
    {
        $reports = ViolationReport::query()
            ->with(['district', 'reservoir', 'reporter'])
            ->forUserDistricts($request->user())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.violations.index', compact('reports'));
    }

    public function show(Request $request, ViolationReport $violation): View
    {
        $this->assertScoped($request, $violation);
        $violation->load(['district', 'office', 'reservoir', 'reporter', 'media', 'assignee']);

        return view('admin.violations.show', ['report' => $violation]);
    }

    public function updateStatus(Request $request, ViolationReport $violation): RedirectResponse
    {
        abort_unless($request->user()->hasModuleAction('violations', 'status'), 403);
        $this->assertScoped($request, $violation);

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
            'assigned_to' => $request->user()->id,
        ]);

        return back()->with('status', 'Violation status updated.');
    }

    private function assertScoped(Request $request, ViolationReport $report): void
    {
        $ids = $request->user()->scopedDistrictIds();
        if ($ids !== null && ! in_array((int) $report->district_id, $ids, true)) {
            abort(403);
        }
    }
}
