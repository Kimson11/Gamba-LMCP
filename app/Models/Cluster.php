<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cluster extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cooperative_id',
        'name',
        'code',
        'supervisor_user_id',
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
     * Supervisor relation if assigned.
     *
     * @return BelongsTo<User, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    /**
     * Members assigned to this cluster.
     *
     * @return HasMany<Member, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
