<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores canonical request fingerprints and replay payloads for idempotent API writes.
 */
class IdempotencyKey extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'idempotency_key',
        'method',
        'path',
        'request_hash',
        'response_status',
        'response_body',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'response_body' => 'array',
    ];
}
