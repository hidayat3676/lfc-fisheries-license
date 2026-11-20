<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PaymirService
{
    public function baseUrl(): string
    {
        return rtrim((string) config('rflms.paymir.base_url', 'https://privatesandbox.kp.gov.pk'), '/');
    }

    public function serviceCode(): string
    {
        return (string) config('rflms.paymir.service_code', 'SRV-0070');
    }

    public function username(): string
    {
        return (string) config('rflms.paymir.username', 'PMR-SRV-0070');
    }

    public function password(): string
    {
        return (string) config('rflms.paymir.password', 'QWERTYU34567ASDFGhwertyu34567ASDFG3456dfgh34567DFug');
    }

    public function paymentKey(): string
    {
        return (string) config('rflms.paymir.payment_key', 'hgYFhvkhFKYUdfyuhvgIHFKTWY76rtuHVhjcjhvKFudhjhcv');
    }

    /**
     * Section 3.2 & 3.5: Base64(UserName + ":" + Password)
     */
    public function encodeCredentials(): string
    {
        return base64_encode($this->username().':'.$this->password());
    }

    /**
     * Section 3.3: Substring(5, 25) of Credentials + "2023KPITB"
     */
    public function deriveEncryptionKey(?string $credentials = null): string
    {
        $credentials ??= $this->encodeCredentials();

        return substr($credentials, 5, 25).'2023KPITB';
    }

    /**
     * Section 3.4 & 3.5: HMAC-SHA256 lowercase hex signature for Push Voucher API
     * strforenc = DPTPaymentID + "&" + CitizenCNIC + "&" + ServiceName + "&" + StatusCode + "&" + FeeAmount + "&" + ServiceKey
     */
    public function computeServiceD(
        string $dptPaymentId,
        string $citizenCnic,
        string $serviceName,
        int $statusCode,
        string|int|float $feeAmount,
        ?string $serviceKey = null
    ): string {
        $serviceKey ??= $this->serviceCode();
        $strforenc = implode('&', [
            $dptPaymentId,
            $citizenCnic,
            $serviceName,
            $statusCode,
            $feeAmount,
            $serviceKey,
        ]);

        return strtolower(hash_hmac('sha256', $strforenc, $this->deriveEncryptionKey()));
    }

    /**
     * Common function used across Web & API license applications to push a voucher to PAYMIR
     * and persist/return the generated PSID and Voucher record on the Application.
     *
     * @return array{success: bool, psid: string, dpt_payment_id: string, arcid: int|null, message: string, voucher: Voucher|null, raw: array<string, mixed>|null}
     */
    public function generatePsidForApplication(Application $application, ?User $citizen = null): array
    {
        $application->loadMissing(['user.citizenProfile', 'category', 'reservoir.district']);
        $citizen ??= $application->user;

        if ($citizen) {
            $this->registerUser($citizen);
        }

        $result = $this->pushVoucher($application, $citizen);

        $psid = $result['psid'];
        $dptPaymentId = $result['dpt_payment_id'];

        $updates = [
            'status' => Application::STATUS_PENDING,
            'psid_code' => $psid,
        ];

        if (empty($application->payment_reference)) {
            $updates['payment_reference'] = $dptPaymentId;
        }

        $application->update($updates);

        $application->payments()
            ->where('status', 'pending')
            ->whereNull('reference')
            ->update(['reference' => $updates['payment_reference'] ?? $application->payment_reference]);

        return $result;
    }

    /**
     * Section 4.1: User Registration API (/api/DepartmentSnippet/RegisterUserDpt)
     *
     * @return array<string, mixed>
     */
    public function registerUser(User $user): array
    {
        $user->loadMissing('citizenProfile');
        $cnic = $this->formatCnic($user->citizenProfile?->cnic);
        $fullName = $user->citizenProfile?->full_name ?: $user->name;
        $mobile = $this->formatMobile($user->mobile ?: $user->citizenProfile?->emergency_contact);
        $email = $user->email ?: 'citizen@fisheries.kp.gov.pk';

        $identityKey = base64_encode(hash('sha256', $fullName.'|'.$email.'|'.$cnic, true));

        $payload = [
            'CNIC' => $cnic,
            'Password' => $identityKey,
            'FullName' => $fullName,
            'MobileNo' => $mobile,
            'EmailAddress' => $email,
            'UserType' => 'NormalUser',
            'RoleId' => 2,
            'Category' => 'Tax on Profession Trades and Calling ADC',
        ];

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl().'/api/DepartmentSnippet/RegisterUserDpt', $payload);

            return (array) $response->json();
        } catch (Throwable $e) {
            Log::warning('PAYMIR RegisterUserDpt request failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'cnic' => $cnic,
            ]);

            return [
                'responseStatus' => false,
                'responseMessage' => $e->getMessage(),
            ];
        }
    }

    /**
     * Section 4.2: Push Voucher API (/api/DepartmentSnippet/ReceiveApplicationsArchiveDataDpt)
     *
     * @return array{success: bool, psid: string, dpt_payment_id: string, arcid: int|null, message: string, raw: array<string, mixed>|null}
     */
    public function pushVoucher(Application $application, ?User $citizen = null): array
    {
        $application->loadMissing(['user.citizenProfile', 'category', 'reservoir.district']);
        $citizen ??= $application->user;

        $dptPaymentId = $this->buildDptPaymentId($application);
        $trackingNumber = $application->application_no;
        $citizenCnic = $this->formatCnic($citizen?->citizenProfile?->cnic);
        $departmentName = (string) config('rflms.paymir.department_name', 'Directorate of Fisheries');
        $serviceName = (string) config('rflms.paymir.service_name', 'Fishing E-License');
        $serviceTypeName = $application->category?->name ?: $serviceName;
        $statusCode = 0;
        $statusMsg = 'Payment not paid yet';

        $rawFee = (float) ($application->fee_amount_snapshot ?: ($application->category?->fee_amount ?? 0));
        $feeAmount = floor($rawFee) == $rawFee ? (int) $rawFee : round($rawFee, 2);

        $expireAt = Carbon::now()->addDays(7);
        $expireDate = $expireAt->format('Y-m-d\TH:i:s.v');
        $serviceKey = $this->serviceCode();
        $tag = (string) config('rflms.paymir.tag', '10023779400053');

        $serviceD = $this->computeServiceD(
            $dptPaymentId,
            $citizenCnic,
            $serviceName,
            $statusCode,
            $feeAmount,
            $serviceKey
        );

        $voucherObject = [
            'dptPaymentID' => $dptPaymentId,
            'trackingNumber' => $trackingNumber,
            'citizenName' => $citizen->citizenProfile->full_name ?: $citizen->name,
            'citizenCNIC' => $citizenCnic,
            'departmentName' => $departmentName,
            'serviceName' => $serviceName,
            'serviceTypeName' => $serviceTypeName,
            'statusCode' => $statusCode,
            'statusMsg' => $statusMsg,
            'feeAmount' => $feeAmount,
            'ePayExpireDate' => $expireDate,
            'serviceKey' => $serviceKey,
            'VoucherAgainst' => 'CNIC',
            'Tag' => $tag,
            'serviceD' => $serviceD,
        ];

        $success = false;
        $psid = null;
        $arcid = null;
        $remarks = null;
        $body = null;

        try {
       
            $response = Http::timeout(12)
                ->withHeaders([
                    'Credentials' => $this->encodeCredentials(),
                    'ServiceCode' => $serviceKey,
                ])
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl().'/api/DepartmentSnippet/ReceiveApplicationsArchiveDataDpt', [$voucherObject]);

            $body = (array) $response->json();
            $archiveData = $body['archiveData'] ?? [];
            $firstRecord = is_array($archiveData) && isset($archiveData[0]) ? (array) $archiveData[0] : null;

            if (! empty($firstRecord['psid'])) {
                $success = true;
                $psid = (string) $firstRecord['psid'];
                $dptPaymentId = (string) ($firstRecord['dptPaymentID'] ?? $dptPaymentId);
                $arcid = isset($firstRecord['arcid']) ? (int) $firstRecord['arcid'] : null;
                $remarks = isset($firstRecord['remarks']) ? (string) $firstRecord['remarks'] : null;
            } else {
                Log::warning('PAYMIR Push Voucher did not return PSID', [
                    'application_id' => $application->id,
                    'dptPaymentID' => $dptPaymentId,
                    'response' => $body,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('PAYMIR Push Voucher HTTP error: '.$e->getMessage(), [
                'application_id' => $application->id,
                'dptPaymentID' => $dptPaymentId,
            ]);
            $body = ['responseStatus' => false, 'responseMessage' => $e->getMessage()];
        }

        if (! $psid) {
            $psid = 'FB101210'.str_pad((string) $application->id, 6, '0', STR_PAD_LEFT).now()->format('His');
        }

        $voucherModel = null;
        if (Schema::hasTable('vouchers')) {
            $voucherModel = Voucher::query()->updateOrCreate(
                [
                    'dpt_payment_id' => $dptPaymentId,
                ],
                [
                    'application_id' => $application->id,
                    'user_id' => $citizen?->id ?? $application->user_id,
                    'tracking_number' => $trackingNumber,
                    'psid' => $psid,
                    'arc_id' => $arcid,
                    'citizen_cnic' => $citizenCnic,
                    'department_name' => $departmentName,
                    'service_name' => $serviceName,
                    'service_type_name' => $serviceTypeName,
                    'service_key' => $serviceKey,
                    'voucher_against' => 'CNIC',
                    'tag' => $tag,
                    'fee_amount' => $rawFee,
                    'status_code' => $statusCode,
                    'status' => Voucher::STATUS_PENDING,
                    'status_msg' => $statusMsg,
                    'epay_expire_date' => $expireAt,
                    'service_d' => $serviceD,
                    'remarks' => $remarks ?? (string) ($body['responseMessage'] ?? null),
                    'request_payload' => $voucherObject,
                    'response_payload' => $body,
                ]
            );
        }

        return [
            'success' => $success,
            'psid' => $psid,
            'dpt_payment_id' => $dptPaymentId,
            'arcid' => $arcid,
            'message' => (string) ($body['responseMessage'] ?? ($success ? 'Success.' : 'Fallback PSID generated.')),
            'voucher' => $voucherModel,
            'raw' => $body,
        ];
    }

    /**
     * Section 4.4: Voucher Tracing API (/api/Voucher/VoucherTrace)
     *
     * @return array<string, mixed>
     */
    public function traceVoucher(string $dptPaymentId): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Credentials' => $this->encodeCredentials(),
                    'ServiceCode' => $this->serviceCode(),
                ])
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl().'/api/Voucher/VoucherTrace', [
                    'DPTPaymentID' => $dptPaymentId,
                ]);

            return (array) $response->json();
        } catch (Throwable $e) {
            return [
                'ResponseStatus' => 'false',
                'ResponseMessage' => $e->getMessage(),
                'ResponseCode' => '500',
            ];
        }
    }

    /**
     * Section 4.5: Voucher Cancellation API (/api/Voucher/CancelVoucher)
     *
     * @return array<string, mixed>
     */
    public function cancelVoucher(string $dptPaymentId): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Credentials' => $this->encodeCredentials(),
                    'ServiceCode' => $this->serviceCode(),
                ])
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl().'/api/Voucher/CancelVoucher', [
                    'DPTPaymentID' => $dptPaymentId,
                ]);

            return (array) $response->json();
        } catch (Throwable $e) {
            return [
                'ResponseStatus' => 'false',
                'ResponseMessage' => $e->getMessage(),
                'ResponseCode' => '500',
            ];
        }
    }

    /**
     * Appendix B: Format PREFIX-CODE-YY-SEQUENCE (e.g. ABCD-3009-26-000001)
     */
    public function buildDptPaymentId(Application $application): string
    {
        $prefix = (string) config('rflms.paymir.dpt_prefix', 'ABCD-3009');
        $yy = now()->format('y');

        return sprintf('%s-%s-%06d', $prefix, $yy, (int) $application->id);
    }

    private function formatCnic(?string $cnic): string
    {
        $digits = preg_replace('/\D/', '', (string) $cnic);
        if (strlen($digits) === 13) {
            return substr($digits, 0, 5).'-'.substr($digits, 5, 7).'-'.substr($digits, 12, 1);
        }

        return $cnic ?: '17301-0000000-1';
    }

    private function formatMobile(?string $mobile): string
    {
        $digits = preg_replace('/\D/', '', (string) $mobile);
        if (str_starts_with($digits, '03') && strlen($digits) === 11) {
            return '+92'.substr($digits, 1);
        }
        if (str_starts_with($digits, '92') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        return '+923000000000';
    }
}

