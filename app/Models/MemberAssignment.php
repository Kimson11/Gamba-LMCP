<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAssignment extends Model
{
    /**
     * Append-only rows do not use updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'member_id',
        'cooperative_id',
        'from_cluster_id',
        'to_cluster_id',
        'assigned_by_user_id',
        'reason',
        'approval_status',
        'approved_by_user_id',
        'approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
        'correlation_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Assignment subject relation.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Destination cluster relation.
     *
     * @return BelongsTo<Cluster, $this>
     */
    public function toCluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'to_cluster_id');
    }

    /**
     * Source cluster relation.
     *
     * @return BelongsTo<Cluster, $this>
     */
    public function fromCluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'from_cluster_id');
    }

    /**
     * Cooperative scope relation.
     *
     * @return BelongsTo<Cooperative, $this>
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    /**
     * Approver relation.
     *
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Rejector relation.
     *
     * @return BelongsTo<User, $this>
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }
}
