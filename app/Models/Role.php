<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a scope assignment for a user.
 *
 * A single user row carries their role (e.g. 'coop_admin') directly.
 * This model holds the *where* that role applies — which country,
 * cooperative, or cluster the user is permitted to operate in.
 *
 * Example: a ClusterSupervisor assigned to clusters #3 and #5 would
 * have two UserScope rows:
 *   { scope_type: 'cluster', scope_id: 3 }
 *   { scope_type: 'cluster', scope_id: 5 }
 *
 * @property int $id
 * @property int $user_id
 * @property string $scope_type One of: 'country', 'cooperative', 'cluster'
 * @property int $scope_id The PK of the scoped entity
 */
class Role extends Model
{
    /**
     * Mass-assignable columns for scope assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'scope_type',  // 'country' | 'cooperative' | 'cluster'
        'scope_id',
    ];

    /**
     * The actual database table for this model is 'user_scopes'.
     * Laravel would default to 'roles', so we override it here.
     */
    protected $table = 'user_scopes';

    /**
     * The user who owns this scope assignment.
     *
     * Usage: $scope->user->email
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
