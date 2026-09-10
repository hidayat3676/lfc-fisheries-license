<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')->withTimestamps();
    }

    public function modulePermissions(): HasMany
    {
        return $this->hasMany(GroupModulePermission::class);
    }

    public function hasModuleAction(string $moduleKey, string $action): bool
    {
        if (! $this->is_active) {
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

        return $this->modulePermissions()
            ->whereHas('module', fn ($q) => $q->where('key', $moduleKey)->where('is_active', true))
            ->where($column, true)
            ->exists();
    }
}
