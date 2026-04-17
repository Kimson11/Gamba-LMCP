<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operational tracking row for notification delivery attempts.
 *
 * This model complements Laravel's built-in notifications table with
 * delivery metadata (status, failure reason, correlation ID).
 */
class NotificationDelivery extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'recipient_user_id',
        'notification_id',
        'notification_type',
        'channel',
        'status',
        'title',
        'message',
        'payload',
        'failure_reason',
        'correlation_id',
        'delivered_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        // Decode payload JSON to associative array automatically.
        'payload' => 'array',
        'delivered_at' => 'datetime',
    ];

    /**
     * Recipient user relation.
     *
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
