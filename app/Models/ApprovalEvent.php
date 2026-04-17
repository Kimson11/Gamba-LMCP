<?php

namespace App\Models;

use App\Enums\ApprovalEventType;
use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable approval event row.
 *
 * IMPORTANT: treat this model as append-only in business logic.
 * Do not update/delete rows after creation.
 */
class ApprovalEvent extends Model
{
    /**
     * There is no updated_at because events are immutable.
     */
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'entity_type',
        'entity_id',
        'event_type',
        'current_status',
        'actor_id',
        'actor_role',
        'reason',
        'metadata',
        'correlation_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        // Cast enum-backed columns to strongly typed enums in PHP.
        'event_type' => ApprovalEventType::class,
        'current_status' => ApprovalStatus::class,
        // Decode metadata JSON into associative array automatically.
        'metadata' => 'array',
    ];

    /**
     * Actor who performed this transition.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
