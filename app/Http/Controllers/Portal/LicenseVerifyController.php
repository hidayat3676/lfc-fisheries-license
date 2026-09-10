<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\SystemSettingService;
use App\Support\PrivacyMask;
use Illuminate\View\View;

class LicenseVerifyController extends Controller
{
    public function __invoke(string $token, SystemSettingService $settings): View
    {
        $cleanToken = trim($token);

        $license = License::query()
            ->with(['user.citizenProfile', 'reservoir.district', 'category'])
            ->where(function ($query) use ($cleanToken) {
                $query->where('qr_token', $cleanToken)
                    ->orWhere('license_no', $cleanToken)
                    ->orWhere('license_no', 'like', '%' . $cleanToken . '%');

                if (is_numeric($cleanToken)) {
                    $query->orWhere('id', (int) $cleanToken);
                }
            })
            ->firstOrFail();

        $valid = $license->status === License::STATUS_ACTIVE
            && $license->expiry_date->endOfDay()->isFuture();

        $status = match (true) {
            $license->status === License::STATUS_CANCELLED => 'Cancelled',
            ! $valid && $license->status === License::STATUS_ACTIVE => 'Expired',
            $valid => 'Valid',
            default => ucfirst($license->status),
        };

        $mode = $settings->qrPrivacy();
        $showCnic = $settings->bool('show_cnic_on_public_qr', false);
        $holderName = $license->user?->citizenProfile?->full_name ?: ($license->user?->name ?: 'N/A');
        $cnic = $license->user?->citizenProfile?->cnic;

        return view('portal.license-verify', [
            'license' => $license,
            'status' => $status,
            'valid' => $valid,
            'holderDisplay' => $holderName,
            'cnicDisplay' => $showCnic ? PrivacyMask::cnic($cnic, $mode) : null,
            'privacyMode' => $mode,
        ]);
    }
}
