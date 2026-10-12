<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contractor extends Model
{
    use SoftDeletes;

    /** Statuses the Perm Sec can set with the status control. */
    public const STATUSES = ['pending', 'active', 'deactivated', 'suspended', 'blacklisted'];

    /** Every status, including 'rejected' (set only through Reject, with a reason). */
    public const ALL_STATUSES = ['pending', 'rejected', 'active', 'deactivated', 'suspended', 'blacklisted'];

    public const STATUS_BADGES = [
        'pending' => 'info', 'rejected' => 'danger', 'active' => 'success',
        'deactivated' => 'secondary', 'suspended' => 'warning', 'blacklisted' => 'dark',
    ];

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /** Locked: details are read-only except for Admin / Commissioner / Perm Sec, who may also delete. */
    public const FROZEN_STATUSES = ['deactivated', 'suspended', 'blacklisted'];

    public function isFrozen(): bool
    {
        return in_array($this->status, self::FROZEN_STATUSES, true);
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->isFrozen() ? $user->isExecutive() : $user->hasPermission('contractors.edit');
    }

    /** Locked or rejected contractors can be deleted, by the executives only. */
    public function canBeDeletedBy(User $user): bool
    {
        return ($this->isFrozen() || $this->isRejected()) && $user->hasPermission('contractors.delete');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContractorDocument::class)->latest();
    }

    protected $fillable = [
        'name', 'rc_number', 'tax_id', 'contact_person', 'address',
        'email', 'phone', 'director', 'performance_score', 'active_contracts_count', 'status', 'rejection_reason',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function representatives(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
