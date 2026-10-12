<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_code', 'name', 'directorate', 'rejection_reason', 'department', 'project_category_id', 'sector', 'description',
        'district_id', 'lga_id', 'ward_id', 'community_id', 'latitude', 'longitude',
        'contractor_id', 'consultant_id', 'supervising_engineer_id',
        'award_date', 'commencement_date', 'expected_completion_date', 'contract_duration_days',
        'status', 'priority', 'funded_by', 'planned_progress_percent', 'actual_progress_percent', 'created_by',
    ];

    protected $casts = [
        'award_date' => 'date',
        'commencement_date' => 'date',
        'expected_completion_date' => 'date',
    ];

    /** Status keys in workflow order, with the wording users see. */
    public const STATUS_LABELS = [
        'pending' => 'Pending approval',
        'rejected' => 'Rejected',
        'planned' => 'Approved',
        'in_progress' => 'In progress',
        'delayed' => 'Delayed',
        'completed' => 'Completed',
        'suspended' => 'Suspended',
        'cancelled' => 'Cancelled',
        'deactivated' => 'Deactivated',
    ];

    /** Locked: nothing on the project may change, except by Admin / Commissioner / Perm Sec. */
    public const FROZEN_STATUSES = ['suspended', 'cancelled', 'deactivated'];

    /** Approved and running: the only projects a contractor can be assigned to. */
    public const ACTIVE_STATUSES = ['planned', 'in_progress', 'delayed'];

    public function isFrozen(): bool { return in_array($this->status, self::FROZEN_STATUSES, true); }
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
    public function isActive(): bool { return in_array($this->status, self::ACTIVE_STATUSES, true); }

    /** Progress, milestones, photos, inspections, contract and payments are only possible once approved and not locked. */
    public function acceptsWork(): bool
    {
        return ! $this->isFrozen() && ! $this->isPending() && ! $this->isRejected();
    }

    /** Locked projects can only be edited by the executives; otherwise projects.edit is needed. */
    public function canBeEditedBy(User $user): bool
    {
        return $this->isFrozen() ? $user->isExecutive() : $user->hasPermission('projects.edit');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    /** The four directorates every project must belong to. */
    public const DIRECTORATES = [
        'civil' => 'Civil Engineering',
        'public_building' => 'Public Building',
        'electrical' => 'Electrical Engineering',
        'mechanical' => 'Mechanical Engineering',
    ];

    /**
     * Limit a query to the projects the given user is allowed to see:
     * everything for Admin / Commissioner / Perm Sec / Planning / Finance,
     * otherwise only the user's own directorate.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->seesAllDirectorates()) {
            return $query;
        }

        return $query->where($query->getModel()->getTable().'.directorate', $user->directorate() ?? '__none__');
    }

    /** Accepts 'civil', 'Public Building', 'electrical engineering'... returns the key or null. */
    public static function directorateKey(?string $value): ?string
    {
        $v = strtolower(trim((string) $value));
        $v = str_replace([' ', '-'], '_', $v);
        foreach (self::DIRECTORATES as $key => $label) {
            if ($v === $key || $v === str_replace(' ', '_', strtolower($label))) {
                return $key;
            }
        }

        return null;
    }

    public function getDirectorateLabelAttribute(): string
    {
        return self::DIRECTORATES[$this->directorate] ?? 'Unassigned';
    }

    // Statuses power the GIS map colors: completed=green, in_progress=blue,
    // suspended=yellow, delayed=red, cancelled=gray.
    public const STATUS_COLORS = [
        'pending' => '#7C3AED',
        'rejected' => '#B91C1C',
        'deactivated' => '#1F2937',
        'planned' => '#94A3B8',
        'in_progress' => '#2563EB',
        'suspended' => '#EAB308',
        'delayed' => '#DC2626',
        'completed' => '#16A34A',
        'cancelled' => '#6B7280',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function district(): BelongsTo { return $this->belongsTo(District::class); }
    public function lga(): BelongsTo { return $this->belongsTo(Lga::class); }
    public function ward(): BelongsTo { return $this->belongsTo(Ward::class); }
    public function community(): BelongsTo { return $this->belongsTo(Community::class); }

    // withTrashed: a deleted contractor's name must stay visible on the projects it worked on
    public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class)->withTrashed(); }
    public function consultant(): BelongsTo { return $this->belongsTo(Consultant::class); }
    public function supervisingEngineer(): BelongsTo { return $this->belongsTo(User::class, 'supervising_engineer_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function contract(): HasOne { return $this->hasOne(Contract::class); }
    public function milestones(): HasMany { return $this->hasMany(ProjectMilestone::class)->orderBy('sequence'); }
    public function updates(): HasMany { return $this->hasMany(ProjectUpdate::class)->latest(); }
    public function inspections(): HasMany { return $this->hasMany(Inspection::class)->latest('inspection_date'); }
    public function images(): HasMany { return $this->hasMany(ProjectImage::class)->latest(); }
    public function documents(): HasMany { return $this->hasMany(ProjectDocument::class)->latest(); }
    public function risks(): HasMany { return $this->hasMany(RiskRegisterEntry::class); }
    public function issues(): HasMany { return $this->hasMany(ProjectIssue::class); }

    public function getDelayDaysAttribute(): int
    {
        return max(0, (int) round($this->planned_progress_percent - $this->actual_progress_percent));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? '#94A3B8';
    }
}
