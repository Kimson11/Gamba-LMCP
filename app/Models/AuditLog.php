<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only record of every meaningful state change in the system.
 *
 * IMPORTANT: This model intentionally disables update() and delete()
 * to enforce the append-only invariant. All writes go through AuditLogger.
 *
 * @property int $id
 * @property int|null $actor_id User who triggered the action (null = system)
 * @property string|null $actor_role Role snapshot at write time (e.g. 'member')
 * @property string $action Dot-notation event (e.g. 'milk_log.created')
 * @property string|null $subject_type Model class name (e.g. 'App\Models\MilkLog')
 * @property int|null $subject_id Primary key of the affected model
 * @property int|null $cooperative_id Cooperative context (null for system events)
 * @property string|null $correlation_id UUID linking related audit rows to one request
 * @property array|null $context Free-form contextual detail
 */
class AuditLog extends Model
{
    /**
     * Disable updated_at — audit rows are never touched after insert.
     */
    public const UPDATED_AT = null;

    /**
     * Mass-assignable columns.
     *
     * @var list<string>
     */
    protected $fillable = [
        'actor_id',
        'actor_role',
        'action',
        'subject_type',
        'subject_id',
        'cooperative_id',
        'correlation_id',
        'context',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        // Parse the JSON 'context' column into a PHP array automatically.
        'context' => 'array',
    ];

    /**
     * The user who triggered this event.
     *
     * Returns null for system-generated audit rows (e.g. scheduled jobs).
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
