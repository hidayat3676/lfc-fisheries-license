Hello {{ $application->user->name }},

@if ($event === 'submitted')
Your fishing licence application {{ $application->application_no }} was submitted and is under review.
Water body: {{ $application->reservoir->name }}
Category: {{ $application->category->name }}
Fee: {{ number_format((float) $application->fee_amount_snapshot, 0) }} {{ $application->currency }}
@elseif ($event === 'approved')
Your application {{ $application->application_no }} has been approved.
Licence number: {{ $license?->license_no }}
Valid: {{ optional($license?->issue_date)->format('d M Y') }} to {{ optional($license?->expiry_date)->format('d M Y') }}
Verify online: {{ $license ? route('license.verify', $license->qr_token) : '' }}
@elseif ($event === 'rejected')
Your application {{ $application->application_no }} was rejected.
Remarks: {{ $application->officer_remarks }}
@elseif ($event === 'info_required')
We need more information for application {{ $application->application_no }}.
Remarks: {{ $application->officer_remarks }}
Please check your account and respond / re-submit payment proof if requested.
@endif

— {{ config('app.name') }}
