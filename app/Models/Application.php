<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_INFO_REQUIRED = 'info_required';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_METHOD_COUNTER_CASH = 'counter_cash';

    public const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';

    public const PAYMENT_METHOD_BANK_DEPOSIT = 'bank_deposit';

    public const PAYMENT_METHOD_1BILL = '1bill';

    public const PAYMENT_METHOD_ONLINE_TRANSFER = 'online_transfer';

    public const PAYMENT_METHOD_ONLINE_PAYMENT = 'online_payment';

    public const PAYMENT_METHOD_PAYMIR = 'paymir';

    public const PAYMENT_METHOD_CASH = 'cash';

    protected $appends = [
        'payment_receipt_url',
        'applied_by_name',
    ];

    public function paymentReceiptUrl(): ?string
    {
        if (! $this->payment_receipt_path) {
            return null;
        }

        $baseUrl = config('app.image_base_url');

        if (! $baseUrl) {
            if ($forwardedHost = request()->header('x-forwarded-host')) {
                $scheme = request()->header('x-forwarded-proto') ?: request()->getScheme();
                $baseUrl = $scheme . '://' . $forwardedHost;
            } else {
                $baseUrl = request()->getSchemeAndHttpHost();
            }
        }

        $path = ltrim($this->payment_receipt_path, '/');
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }
        if (! str_starts_with($path, 'storage/')) {
            $path = 'storage/' . $path;
        }

        return rtrim($baseUrl, '/') . '/' . $path;
    }

    public function getPaymentReceiptUrlAttribute(): ?string
    {
        return $this->paymentReceiptUrl();
    }

    public static function paymentMethods(): array
    {
        return [
            self::PAYMENT_METHOD_1BILL,
            self::PAYMENT_METHOD_PAYMIR,
            self::PAYMENT_METHOD_ONLINE_PAYMENT,
        ];
    }

    public static function isOnlinePaymentMethod(?string $method): bool
    {
        return in_array($method, [
            self::PAYMENT_METHOD_1BILL,
            self::PAYMENT_METHOD_PAYMIR,
            self::PAYMENT_METHOD_ONLINE_PAYMENT,
        ], true);
    }

    protected $fillable = [
        'application_no',
        'user_id',
        'applied_by',
        'reservoir_id',
        'category_id',
        'fishing_start_date',
        'fishing_end_date',
        'fee_amount_snapshot',
        'currency',
        'status',
        'payment_method',
        'payment_reference',
        'payment_receipt_path',
        'psid_code',
        'officer_remarks',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'fishing_start_date' => 'date',
            'fishing_end_date' => 'date',
            'fee_amount_snapshot' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function getAppliedByNameAttribute(): ?string
    {
        if (is_null($this->applied_by)) {
            return null;
        }

        if ($this->relationLoaded('appliedBy')) {
            return $this->appliedBy?->name;
        }

        return $this->appliedBy()->value('name');
    }

    public function reservoir(): BelongsTo
    {
        return $this->belongsTo(Reservoir::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class, 'category_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function license(): HasOne
    {
        return $this->hasOne(License::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class)->latestOfMany();
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function scopeForUserDistricts(Builder $query, User $user): Builder
    {
        $ids = $user->scopedDistrictIds();
        if ($ids === null) {
            return $query;
        }

        return $query->whereHas('reservoir', fn ($q) => $q->whereIn('district_id', $ids));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under review',
            self::STATUS_INFO_REQUIRED => 'Info required',
            self::STATUS_APPROVED => 'Approved / Issued',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function isPendingReview(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW, self::STATUS_INFO_REQUIRED], true);
    }

    public function isPaid(): bool
    {
        if ($this->status === self::STATUS_APPROVED) {
            return true;
        }

        if ($this->relationLoaded('payments')) {
            return $this->payments->contains(fn ($p) => in_array($p->status, ['verified', 'paid'], true));
        }

        return $this->payments()->whereIn('status', ['verified', 'paid'])->exists();
    }
}
