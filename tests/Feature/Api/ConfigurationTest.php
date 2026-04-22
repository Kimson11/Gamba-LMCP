<?php

use App\Models\ConfigurationChangeEvent;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows privileged users with elevated session proof to upsert and list configuration values', function () {
    $user = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    $trustedDevice = TrustedDevice::query()->create([
        'user_id' => $user->id,
        'device_fingerprint' => 'device-fingerprint-abcdef1234567890',
        'device_name' => 'Admin Console',
    ]);

    $headers = [
        'Idempotency-Key' => 'config-upsert-key-001',
        'X-MFA-Verified' => 'true',
        'X-Trusted-Device-Id' => (string) $trustedDevice->id,
    ];

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/configurations/sync.batch.max_items', [
            'scope_type' => 'global',
            'scope_id' => 0,
            'value' => [
                'max_items' => 500,
                'retry_limit' => 3,
            ],
        ], $headers)
        ->assertSuccessful()
        ->assertJsonPath('data.key', 'sync.batch.max_items')
        ->assertJsonPath('data.value.max_items', 500);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/configurations?key=sync.batch.max_items', [
            'X-MFA-Verified' => 'true',
            'X-Trusted-Device-Id' => (string) $trustedDevice->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.key', 'sync.batch.max_items')
        ->assertJsonPath('data.items.0.value.retry_limit', 3);

    expect(ConfigurationChangeEvent::query()->count())->toBe(1);
});
