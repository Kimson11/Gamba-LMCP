<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

it('returns the v1 ping response with meta envelope', function () {
    $response = $this->getJson('/api/v1/system/ping');

    $response
        ->assertSuccessful()
        ->assertHeader('X-Request-Id')
        ->assertHeader('X-Correlation-Id')
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('data.status', 'ok')
            ->where('data.version', 'v1')
            ->whereType('data.service', 'string')
            ->whereType('meta.request_id', 'string')
            ->whereType('meta.timestamp', 'string')
            ->etc()
        );
});

it('replays the same response for a duplicate idempotency key', function () {
    $headers = [
        'Idempotency-Key' => 'foundation-test-key',
    ];

    $payload = [
        'value' => 'first',
    ];

    $firstResponse = $this->postJson('/api/v1/system/idempotent-echo', $payload, $headers);
    $secondResponse = $this->postJson('/api/v1/system/idempotent-echo', $payload, $headers);

    $firstResponse
        ->assertCreated()
        ->assertJsonPath('data.echo', 'first');

    $secondResponse
        ->assertCreated()
        ->assertHeader('X-Idempotency-Replayed', 'true')
        ->assertJsonPath('data.echo', 'first');
});

it('returns conflict for duplicate key with different payload', function () {
    $headers = [
        'Idempotency-Key' => 'foundation-mismatch-key',
    ];

    $this->postJson('/api/v1/system/idempotent-echo', [
        'value' => 'first',
    ], $headers)->assertCreated();

    $response = $this->postJson('/api/v1/system/idempotent-echo', [
        'value' => 'second',
    ], $headers);

    $response
        ->assertStatus(409)
        ->assertJsonPath('code', 'duplicate_with_payload_mismatch');
});

it('returns bad request for write request without idempotency key', function () {
    $response = $this->postJson('/api/v1/system/idempotent-echo', [
        'value' => 'value-without-key',
    ]);

    $response
        ->assertBadRequest()
        ->assertJsonPath('code', 'idempotency_key_required');
});

it('returns standardized validation errors for api requests', function () {
    $response = $this->postJson('/api/v1/system/idempotent-echo', [
        'value' => str_repeat('a', 300),
    ], [
        'Idempotency-Key' => 'validation-key',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonStructure([
            'message',
            'code',
            'errors' => ['value'],
            'meta' => ['request_id', 'timestamp'],
        ]);
});
