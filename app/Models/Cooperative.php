<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cooperative extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'country_code',
        'status',
    ];

    /**
     * Clusters that belong to this cooperative.
     *
     * @return HasMany<Cluster, $this>
     */
    public function clusters(): HasMany
    {
        return $this->hasMany(Cluster::class);
    }

    /**
     * Members that belong to this cooperative.
     *
     * @return HasMany<Member, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
