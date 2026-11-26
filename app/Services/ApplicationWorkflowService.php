<?php

namespace App\Services;

use App\Mail\ApplicationStatusMail;
use App\Models\Application;
use App\Models\License;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ApplicationWorkflowService
{
    public function __construct(private LicensingPolicyService $policy) {}

    public function nextApplicationNo(): string
    {
        $year = now()->format('Y');
        $prefix = "APP-{$year}-";

        $latestNo = Application::query()
            ->where('application_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('application_no');

        $latestSeq = 0;
        if (is_string($latestNo) && preg_match('/^APP-'.preg_quote($year, '/').'-(\d+)$/', $latestNo, $matches)) {
            $latestSeq = (int) $matches[1];
        }

        $seq = max(
            $latestSeq + 1,
            Application::query()->whereYear('created_at', $year)->count() + 1
        );

        do {
            $candidate = sprintf('APP-%s-%05d', $year, $seq);
            $seq++;
        } while (Application::query()->where('application_no', $candidate)->exists());

        return $candidate;
    }

    public function nextLicenseNo(?Application $application = null): string
    {
        if ($application) {
            $application->loadMissing('reservoir.district');
            $district = $application->reservoir?->district;
            $districtCode = trim((string) ($district?->code ?? ''));

            if (strlen($districtCode) === 3) {
                $distPrefix = strtoupper($districtCode);
            } else {
                $districtName = (string) ($district?->name ?? 'KPK');
                $cleanDistrict = preg_replace('/[^A-Za-z]/', '', $districtName);
                $distPrefix = str_pad(strtoupper(substr($cleanDistrict ?: 'KPK', 0, 3)), 3, 'X');
            }

            $monthYear = now()->format('my');
            $actorId = $application->applied_by ?: $application->user_id;

            $base = sprintf('%s-%s-%s', $distPrefix, $monthYear, $actorId);
            $candidate = $base;
            $seq = 2;

            while (License::query()->where('license_no', $candidate)->exists()) {
                $candidate = sprintf('%s-%d', $base, $seq);
                $seq++;
            }

            return $candidate;
        }

        $year = now()->format('Y');
        $count = License::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('LIC-%s-%05d', $year, $count);
    }

    public function approve(Application $application, ?User $officer = null, ?string $remarks = null): License
    {
        $officerId = $officer?->id;

        $license = DB::transaction(function () use ($application, $officerId, $remarks) {
            $application->loadMissing(['category', 'license']);
            $category = $application->category;
            $issueDate = now()->startOfDay();
            $expiry = $this->policy->calculateEndDate($category, $application->fishing_start_date);

            $application->update([
                'status' => Application::STATUS_APPROVED,
                'officer_remarks' => $remarks ?? $application->officer_remarks,
                'reviewed_by' => $officerId ?? $application->reviewed_by,
                'reviewed_at' => now(),
                'fishing_end_date' => $expiry->toDateString(),
            ]);

            $application->payments()
                ->update([
                    'status' => 'verified',
                    'verified_by' => $officerId,
                    'verified_at' => now(),
                ]);

            if ($application->license) {
                $application->license->update([
                    'status' => License::STATUS_ACTIVE,
                    'expiry_date' => $expiry->toDateString(),
                ]);

                return $application->license;
            }

            return License::query()->create([
                'license_no' => $this->nextLicenseNo($application),
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'reservoir_id' => $application->reservoir_id,
                'category_id' => $application->category_id,
                'issue_date' => $issueDate->toDateString(),
                'expiry_date' => $expiry->toDateString(),
                'status' => License::STATUS_ACTIVE,
                'qr_token' => Str::random(40),
                'issued_by' => $officerId,
            ]);
        });

        try {
            $this->notify($application->fresh(['user', 'reservoir', 'category', 'license']), 'approved');
        } catch (\Throwable) {
            // Do not fail license issuance if mail transport is unavailable
        }

        return $license;
    }

    public function reject(Application $application, User $officer, string $remarks): void
    {
        $application->update([
            'status' => Application::STATUS_REJECTED,
            'officer_remarks' => $remarks,
            'reviewed_by' => $officer->id,
            'reviewed_at' => now(),
        ]);

        $this->notify($application->fresh(['user', 'reservoir', 'category']), 'rejected');
    }

    public function requestInfo(Application $application, User $officer, string $remarks): void
    {
        $application->update([
            'status' => Application::STATUS_INFO_REQUIRED,
            'officer_remarks' => $remarks,
            'reviewed_by' => $officer->id,
            'reviewed_at' => now(),
        ]);

        $this->notify($application->fresh(['user', 'reservoir', 'category']), 'info_required');
    }

    public function createPaymentLedger(Application $application): Payment
    {
        return Payment::query()->create([
            'application_id' => $application->id,
            'method' => (string) $application->payment_method,
            'amount' => $application->fee_amount_snapshot,
            'currency' => $application->currency,
            'reference' => $application->payment_reference,
            'paid_at' => now(),
            'status' => 'pending',
        ]);
    }

    public function notifySubmitted(Application $application): void
    {
        $this->notify($application->fresh(['user', 'reservoir', 'category']), 'submitted');
    }

    private function notify(Application $application, string $event): void
    {
        if (! $application->user?->email) {
            return;
        }

        Mail::to($application->user->email)->send(new ApplicationStatusMail($application, $event));
    }
}
