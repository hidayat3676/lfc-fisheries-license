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
        $count = Application::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('APP-%s-%05d', $year, $count);
    }

    public function nextLicenseNo(): string
    {
        $year = now()->format('Y');
        $count = License::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('LIC-%s-%05d', $year, $count);
    }

    public function approve(Application $application, User $officer, ?string $remarks = null): License
    {
        $license = DB::transaction(function () use ($application, $officer, $remarks) {
            $category = $application->category;
            $issueDate = now()->startOfDay();
            $expiry = $this->policy->calculateEndDate($category, $application->fishing_start_date);

            $application->update([
                'status' => Application::STATUS_APPROVED,
                'officer_remarks' => $remarks,
                'reviewed_by' => $officer->id,
                'reviewed_at' => now(),
                'fishing_end_date' => $expiry->toDateString(),
            ]);

            $application->payments()
                ->where('status', 'pending')
                ->update([
                    'status' => 'verified',
                    'verified_by' => $officer->id,
                    'verified_at' => now(),
                ]);

            return License::query()->create([
                'license_no' => $this->nextLicenseNo(),
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'reservoir_id' => $application->reservoir_id,
                'category_id' => $application->category_id,
                'issue_date' => $issueDate->toDateString(),
                'expiry_date' => $expiry->toDateString(),
                'status' => License::STATUS_ACTIVE,
                'qr_token' => Str::random(40),
                'issued_by' => $officer->id,
            ]);
        });

        $this->notify($application->fresh(['user', 'reservoir', 'category', 'license']), 'approved');

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
