<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilkProductionLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cooperative_id',
        'cluster_id',
        'member_id',
        'recorded_by_user_id',
        'quantity_liters',
        'production_date',
        'source',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_liters' => 'decimal:2',
        'production_date' => 'date',
    ];

    /**
     * Cooperative relation.
     *
     * @return BelongsTo<Cooperative, $this>
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    /**
     * Cluster relation.
     *
     * @return BelongsTo<Cluster, $this>
     */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    /**
     * Member relation.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
