<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\License;
use App\Models\User;
use App\Models\ViolationReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const TYPES = [
        'applications' => 'Licence applications',
        'licenses' => 'Issued licences',
        'violations' => 'Violation reports',
    ];

    public function index(Request $request): View
    {
        return view('admin.reports.index', [
            'types' => self::TYPES,
            'filters' => $this->filters($request),
        ]);
    }

    public function export(Request $request): StreamedResponse|View
    {
        $data = $request->validate([
            'type' => ['required', 'in:applications,licenses,violations'],
            'format' => ['required', 'in:csv,print'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'max:64'],
        ]);

        $rows = $this->rows($request, $data['type']);
        $title = self::TYPES[$data['type']];
        $filters = $this->filters($request);

        if ($data['format'] === 'print') {
            return view('admin.reports.print', compact('rows', 'title', 'filters'));
        }

        $filename = 'rflms-'.$data['type'].'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            if ($rows === []) {
                fputcsv($out, ['No records']);
                fclose($out);

                return;
            }
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, array_values($row));
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{type: string, format: string, date_from: ?string, date_to: ?string, status: ?string}
     */
    private function filters(Request $request): array
    {
        return [
            'type' => $request->string('type')->toString() ?: 'applications',
            'format' => $request->string('format')->toString() ?: 'csv',
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'status' => $request->input('status'),
        ];
    }

    /**
     * @return list<array<string, string|int|float|null>>
     */
    private function rows(Request $request, string $type): array
    {
        $user = $request->user();
        $from = $request->filled('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : null;
        $to = $request->filled('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : null;
        $status = $request->input('status');

        return match ($type) {
            'applications' => $this->applicationRows($user, $from, $to, $status),
            'licenses' => $this->licenseRows($user, $from, $to, $status),
            'violations' => $this->violationRows($user, $from, $to, $status),
        };
    }

    /**
     * @return list<array<string, string|int|float|null>>
     */
    private function applicationRows(User $user, ?Carbon $from, ?Carbon $to, ?string $status): array
    {
        $q = Application::query()
            ->with(['user', 'reservoir.district', 'category'])
            ->forUserDistricts($user)
            ->latest();

        if ($from) {
            $q->where('created_at', '>=', $from);
        }
        if ($to) {
            $q->where('created_at', '<=', $to);
        }
        if ($status) {
            $q->where('status', $status);
        }

        return $q->limit(5000)->get()->map(fn (Application $app) => [
            'Application No' => $app->application_no,
            'Citizen' => $app->user?->name,
            'Email' => $app->user?->email,
            'District' => $app->reservoir?->district?->name,
            'Water body' => $app->reservoir?->name,
            'Category' => $app->category?->name,
            'Fee (PKR)' => $app->fee_amount_snapshot,
            'Status' => $app->statusLabel(),
            'Submitted' => optional($app->created_at)->format('Y-m-d H:i'),
            'Reviewed' => optional($app->reviewed_at)->format('Y-m-d H:i'),
        ])->all();
    }

    /**
     * @return list<array<string, string|int|float|null>>
     */
    private function licenseRows(User $user, ?Carbon $from, ?Carbon $to, ?string $status): array
    {
        $q = License::query()
            ->with(['user', 'reservoir.district', 'category'])
            ->whereHas('reservoir', function ($q) use ($user) {
                $ids = $user->scopedDistrictIds();
                if ($ids !== null) {
                    $q->whereIn('district_id', $ids);
                }
            })
            ->latest('issue_date');

        if ($from) {
            $q->whereDate('issue_date', '>=', $from->toDateString());
        }
        if ($to) {
            $q->whereDate('issue_date', '<=', $to->toDateString());
        }
        if ($status) {
            $q->where('status', $status);
        }

        return $q->limit(5000)->get()->map(fn (License $lic) => [
            'Licence No' => $lic->license_no,
            'Citizen' => $lic->user?->name,
            'Email' => $lic->user?->email,
            'District' => $lic->reservoir?->district?->name,
            'Water body' => $lic->reservoir?->name,
            'Category' => $lic->category?->name,
            'Issue date' => optional($lic->issue_date)->format('Y-m-d'),
            'Expiry date' => optional($lic->expiry_date)->format('Y-m-d'),
            'Status' => ucfirst($lic->status),
        ])->all();
    }

    /**
     * @return list<array<string, string|int|float|null>>
     */
    private function violationRows(User $user, ?Carbon $from, ?Carbon $to, ?string $status): array
    {
        $q = ViolationReport::query()
            ->with(['district', 'reservoir'])
            ->forUserDistricts($user)
            ->latest();

        if ($from) {
            $q->where('created_at', '>=', $from);
        }
        if ($to) {
            $q->where('created_at', '<=', $to);
        }
        if ($status) {
            $q->where('status', $status);
        }

        return $q->limit(5000)->get()->map(fn (ViolationReport $r) => [
            'Report No' => $r->report_no,
            'District' => $r->district?->name,
            'Water body' => $r->reservoir?->name,
            'Type' => $r->typeLabel(),
            'Status' => $r->statusLabel(),
            'Reporter' => $r->reporter_name ?: 'Anonymous',
            'Phone' => $r->reporter_phone,
            'Occurred' => optional($r->occurred_at)->format('Y-m-d H:i'),
            'Submitted' => optional($r->created_at)->format('Y-m-d H:i'),
        ])->all();
    }
}
