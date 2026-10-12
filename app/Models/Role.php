<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Only roles in the current role set can be assigned to users. */
    public function scopeAssignable($query)
    {
        return $query->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Current role set (per "Updated Roles 08/10/2026 from Perm Sec")
    |--------------------------------------------------------------------------
    */
    public const SUPER_ADMIN = 'super_admin';
    public const COMMISSIONER = 'commissioner';
    public const PERMANENT_SECRETARY = 'permanent_secretary';
    public const DIRECTOR_CIVIL = 'director_civil';
    public const DIRECTOR_PUBLIC_BUILDING = 'director_public_building';
    public const DIRECTOR_ELECTRICAL = 'director_electrical';
    public const DIRECTOR_MECHANICAL = 'director_mechanical';
    public const DIRECTOR_PLANNING = 'director_planning';
    public const DIRECTOR_FINANCE = 'director_finance';
    public const RESIDENT_CIVIL = 'resident_engineer_civil';
    public const RESIDENT_PUBLIC_BUILDING = 'resident_engineer_public_building';
    public const RESIDENT_ELECTRICAL = 'resident_engineer_electrical';
    public const RESIDENT_MECHANICAL = 'resident_engineer_mechanical';

    /** The Director of each directorate. */
    public const DIRECTORS = [
        self::DIRECTOR_CIVIL, self::DIRECTOR_PUBLIC_BUILDING, self::DIRECTOR_ELECTRICAL, self::DIRECTOR_MECHANICAL,
    ];

    /** The Resident Engineer of each directorate. */
    public const RESIDENTS = [
        self::RESIDENT_CIVIL, self::RESIDENT_PUBLIC_BUILDING, self::RESIDENT_ELECTRICAL, self::RESIDENT_MECHANICAL,
    ];

    /** Super Admin, Commissioner, Permanent Secretary: the only roles that may delete, or touch locked records. */
    public const EXECUTIVES = [self::SUPER_ADMIN, self::COMMISSIONER, self::PERMANENT_SECRETARY];

    /**
     * Roles that are tied to ONE directorate. Whoever holds the role only
     * sees (and can act on) projects of that directorate.
     */
    public const DIRECTORATE_BY_ROLE = [
        self::DIRECTOR_CIVIL => 'civil',
        self::DIRECTOR_PUBLIC_BUILDING => 'public_building',
        self::DIRECTOR_ELECTRICAL => 'electrical',
        self::DIRECTOR_MECHANICAL => 'mechanical',
        self::RESIDENT_CIVIL => 'civil',
        self::RESIDENT_PUBLIC_BUILDING => 'public_building',
        self::RESIDENT_ELECTRICAL => 'electrical',
        self::RESIDENT_MECHANICAL => 'mechanical',
    ];

    /** Roles that see projects of EVERY directorate. */
    public const ALL_DIRECTORATE_ROLES = [
        self::SUPER_ADMIN,
        self::COMMISSIONER,
        self::PERMANENT_SECRETARY,
        self::DIRECTOR_PLANNING,
        self::DIRECTOR_FINANCE,
    ];

    /**
     * Roles from the previous 12-role design. They are retired (not deleted,
     * so old records that point at them stay valid) by RoleAndPermissionSeeder.
     */
    public const RETIRED_ROLES = [
        'director_of_projects', 'director_of_finance', 'procurement_officer',
        'project_engineer', 'me_officer', 'gis_officer', 'contractor_representative',
        'auditor', 'public_viewer',
    ];
}
