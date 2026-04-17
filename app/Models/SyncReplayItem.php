<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stores one reconciliation outcome per unique sync replay item.
 *
 * This is not an append-only event stream. A row represents the authoritative
 * outcome for a specific (scope + client_request_id + request_type) tuple.
 */
class SyncReplayItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'actor_id',
        'cooperative_scope_id',
        'client_request_id',
        'request_type',
        'offline_class',
        'payload_hash',
        'status',
        'entity_type',
        'entity_id',
        'server_state',
        'conflict_code',
        'server_state_summary',
        'resolution_required',
        'resolution_options',
        'resolution_action',
        'resolution_reason',
        'resolved_by_user_id',
        'resolved_at',
        'error_message',
        'processed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'server_state_summary' => 'array',
        'resolution_options' => 'array',
        'resolution_required' => 'boolean',
        'resolved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Actor who owns this replay item.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * User who resolved this conflict row.
     *
     * This relation is null when the row has never been resolved.
     *
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /**
     * Append-only history actions for this conflict row.
     *
     * @return HasMany<SyncConflictAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(SyncConflictAction::class, 'sync_replay_item_id');
    }
}
