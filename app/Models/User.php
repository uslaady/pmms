<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const STATUSES = ['active', 'deactivated', 'suspended', 'blacklisted'];

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role_id', 'contractor_id',
        'department', 'is_active', 'status', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime', // missing cast caused diffForHumans() on string
        'password' => 'hashed',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    /**
     * Check a single permission by name, e.g. $user->can('projects.approve')
     * Works alongside Laravel's native Gate via the can() call.
     */
    protected ?array $permissionCache = null;

    public function hasPermission(string $permission): bool
    {
        // One query per request instead of one per check (the sidebar alone does ~10).
        $this->permissionCache ??= $this->role?->permissions()->pluck('name')->all() ?? [];

        return in_array($permission, $this->permissionCache, true);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role?->name, $roles, true);
    }

    /** Directorate this user is tied to (Directors / Resident Engineers), or null. */
    public function directorate(): ?string
    {
        return Role::DIRECTORATE_BY_ROLE[$this->role?->name] ?? null;
    }

    /** True for roles that work across all four directorates. */
    public function seesAllDirectorates(): bool
    {
        return in_array($this->role?->name, Role::ALL_DIRECTORATE_ROLES, true);
    }

    /**
     * Who may change another account's status / password:
     *  - Super Admin: anyone
     *  - Commissioner: anyone except a Super Admin
     *  - Permanent Secretary: anyone except Super Admin and Commissioner
     */
    /** Message shown when a non-active account tries to sign in or is signed out. */
    public static function statusMessage(string $status): string
    {
        return "Your account is {$status}. You cannot sign in. "
            .'Please contact the Office of the Permanent Secretary or the system administrator.';
    }

    public function isExecutive(): bool
    {
        return $this->hasRole(...Role::EXECUTIVES);
    }

    public function canManageUser(User $target): bool
    {
        $targetRole = $target->role?->name;

        return match ($this->role?->name) {
            Role::SUPER_ADMIN => true,
            Role::COMMISSIONER => $targetRole !== Role::SUPER_ADMIN,
            Role::PERMANENT_SECRETARY => ! in_array($targetRole, [Role::SUPER_ADMIN, Role::COMMISSIONER], true),
            default => false,
        };
    }
}
