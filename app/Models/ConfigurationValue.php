<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a mutable system configuration value by key and scope.
 */
class ConfigurationValue extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'scope_type',
        'scope_id',
        'value',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * Last actor who updated this config value.
     *
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * Append-only change events for this key/scope combination.
     *
     * @return HasMany<ConfigurationChangeEvent, $this>
     */
    public function changeEvents(): HasMany
    {
        return $this->hasMany(ConfigurationChangeEvent::class);
    }
}
