<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const TYPE_SUPER_ADMIN = 'super_admin';

    public const TYPE_ADMIN = 'admin';

    public const TYPE_EXECUTIVE = 'executive';

    public const TYPE_CITIZEN = 'citizen';

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'user_type',
        'all_districts',
        'is_active',
        'pattern_lock_enabled',
        'pattern_lock_hash',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pattern_lock_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'all_districts' => 'boolean',
            'is_active' => 'boolean',
            'pattern_lock_enabled' => 'boolean',
        ];
    }

    public function hasPatternLock(): bool
    {
        return $this->isCitizen() && $this->pattern_lock_enabled && ! empty($this->pattern_lock_hash);
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_type === self::TYPE_SUPER_ADMIN;
    }

    public function isExecutive(): bool
    {
        return $this->user_type === self::TYPE_EXECUTIVE;
    }

    public function isAdminStaff(): bool
    {
        return in_array($this->user_type, [self::TYPE_SUPER_ADMIN, self::TYPE_ADMIN, self::TYPE_EXECUTIVE], true);
    }

    public function isCitizen(): bool
    {
        return $this->user_type === self::TYPE_CITIZEN;
    }

    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class, 'user_districts')->withTimestamps();
    }

    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class, 'user_offices')->withTimestamps();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')->withTimestamps();
    }

    public function modulePermissions(): HasMany
    {
        return $this->hasMany(UserModulePermission::class);
    }

    public function citizenProfile(): HasOne
    {
        return $this->hasOne(CitizenProfile::class);
    }

    public function adminProfile(): HasOne
    {
        return $this->hasOne(AdminProfile::class);
    }

    public function hasModuleAction(string $moduleKey, string $action): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isAdminStaff() || ! $this->is_active) {
            return false;
        }

        $column = match ($action) {
            'view' => 'can_view',
            'create' => 'can_create',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
            'status' => 'can_status',
            'approve' => 'can_approve',
            default => null,
        };

        if ($column === null) {
            return false;
        }

        // Check permissions via active assigned groups
        $hasGroupPermission = $this->groups()
            ->where('is_active', true)
            ->whereHas('modulePermissions', function ($q) use ($moduleKey, $column) {
                $q->whereHas('module', fn ($m) => $m->where('key', $moduleKey)->where('is_active', true))
                    ->where($column, true);
            })
            ->exists();

        if ($hasGroupPermission) {
            return true;
        }

        // Fallback to legacy direct user permissions
        return $this->modulePermissions()
            ->whereHas('module', fn ($q) => $q->where('key', $moduleKey)->where('is_active', true))
            ->where($column, true)
            ->exists();
    }

    /**
     * @return list<int>|null null means all districts
     */
    public function scopedDistrictIds(): ?array
    {
        if ($this->isSuperAdmin() || $this->all_districts) {
            return null;
        }

        return $this->districts()->pluck('districts.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return list<int>|null null means all offices (Super Admin)
     */
    public function scopedOfficeIds(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        return $this->offices()->pluck('offices.id')->map(fn ($id) => (int) $id)->all();
    }

    public function hasCompleteCitizenProfile(): bool
    {
        return $this->isCitizen()
            && $this->citizenProfile
            && $this->citizenProfile->isComplete()
            && filled($this->mobile);
    }

    /**
     * Find user record by citizen CNIC.
     */
    public static function findByCnic(string $cnic): ?self
    {
        $profile = CitizenProfile::findByCnic($cnic);

        return $profile?->user;
    }
}
