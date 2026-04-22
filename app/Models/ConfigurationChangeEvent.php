<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only event stream for system configuration updates.
 */
class ConfigurationChangeEvent extends Model
{
    use HasFactory;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'configuration_value_id',
        'key',
        'scope_type',
        'scope_id',
        'previous_value',
        'new_value',
        'actor_id',
        'correlation_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'previous_value' => 'array',
            'new_value' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Parent configuration value row.
     *
     * @return BelongsTo<ConfigurationValue, $this>
     */
    public function configurationValue(): BelongsTo
    {
        return $this->belongsTo(ConfigurationValue::class);
    }

    /**
     * Actor that made the change.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
