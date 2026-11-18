<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'application_id',
        'user_id',
        'dpt_payment_id',
        'tracking_number',
        'psid',
        'arc_id',
        'citizen_cnic',
        'department_name',
        'service_name',
        'service_type_name',
        'service_key',
        'voucher_against',
        'tag',
        'fee_amount',
        'status_code',
        'status',
        'status_msg',
        'epay_expire_date',
        'service_d',
        'remarks',
        'transaction_id',
        'transaction_amount',
        'transaction_date',
        'synching_status',
        'request_payload',
        'response_payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'fee_amount' => 'decimal:2',
            'transaction_amount' => 'decimal:2',
            'status_code' => 'integer',
            'arc_id' => 'integer',
            'epay_expire_date' => 'datetime',
            'paid_at' => 'datetime',
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

