<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cooperative_id',
        'cluster_id',
        'user_id',
        'member_number',
        'first_name',
        'last_name',
        'phone',
        'status',
    ];

    /**
     * Cooperative parent relation.
     *
     * @return BelongsTo<Cooperative, $this>
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    /**
     * Cluster relation if assigned.
     *
     * @return BelongsTo<Cluster, $this>
     */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    /**
     * Linked user account relation if created.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Append-only assignment timeline for this member.
     *
     * @return HasMany<MemberAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(MemberAssignment::class);
    }
}
