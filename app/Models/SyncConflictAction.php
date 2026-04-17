<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only action stream for sync conflict lifecycle events.
 */
class SyncConflictAction extends Model
{
    /**
     * Append-only rows do not use updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sync_replay_item_id',
        'actor_id',
        'actor_role',
        'action_type',
        'note',
        'metadata',
        'correlation_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Parent replay/conflict row relation.
     *
     * @return BelongsTo<SyncReplayItem, $this>
     */
    public function conflict(): BelongsTo
    {
        return $this->belongsTo(SyncReplayItem::class, 'sync_replay_item_id');
    }

    /**
     * Actor relation for this history action.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
