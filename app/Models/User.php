<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * The central user model for the LMCP platform.
 *
 * Each user has:
 *  - one role (e.g. UserRole::Member, UserRole::CoopAdmin)
 *  - one or more scope assignments that define *where* the role applies
 *  - API tokens managed by Sanctum for mobile/API authentication
 *
 * The role is stored as a string enum column directly on this model.
 * Scopes are stored in the 'user_scopes' table (via the Role model).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 */
#[Fillable(['name', 'email', 'password', 'role', 'mfa_enabled', 'mfa_secret_rotated_at'])]
#[Hidden(['password', 'remember_token', 'mfa_secret_encrypted'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Attribute casting rules.
     *
     * 'role' is cast to the UserRole enum so we can use
     * $user->role === UserRole::Member rather than comparing raw strings.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Cast the raw 'member' string to UserRole::Member automatically
            'role' => UserRole::class,
            'mfa_enabled' => 'boolean',
            'mfa_secret_rotated_at' => 'datetime',
        ];
    }

    /**
     * All scope assignments for this user.
     *
     * A user may have multiple scopes (e.g. a supervisor assigned to
     * two clusters). Use this relationship to check resource access.
     *
     * Usage:
     *   $user->scopes->where('scope_type', 'cooperative')->first()->scope_id
     *
     * @return HasMany<Role, $this>
     */
    public function scopes(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * All trusted devices linked to this user.
     *
     * These records are used by privileged-session middleware to ensure
     * high-risk transitions only happen from enrolled, non-revoked devices.
     *
     * @return HasMany<TrustedDevice, $this>
     */
    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    /**
     * Quick check: does the user operate within a specific cooperative?
     *
     * Example:
     *   $user->hasCooperativeScope(7) // true if assigned to cooperative #7
     */
    public function hasCooperativeScope(int $cooperativeId): bool
    {
        return $this->scopes()
            ->where('scope_type', 'cooperative')
            ->where('scope_id', $cooperativeId)
            ->exists();
    }

    /**
     * Whether this user's role requires MFA / privileged-session enforcement.
     *
     * Delegates to the UserRole enum's isPrivileged() helper.
     * Usage: if ($user->isPrivileged()) { abort(403, 'MFA required'); }
     */
    public function isPrivileged(): bool
    {
        return $this->role->isPrivileged();
    }
}
