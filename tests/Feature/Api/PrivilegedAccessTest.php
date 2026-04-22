<?php

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can enable mfa and enroll a trusted device', function () {
    $user = User::factory()->coopAdmin()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/security/mfa/enable', [
            'secret' => 'seed-secret-value-12345',
        ], [
            'Idempotency-Key' => 'mfa-enable-key',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.mfa_enabled', true);

    $enrollResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/security/trusted-devices', [
            'device_fingerprint' => 'device-fingerprint-1234567890',
            'device_name' => 'Operations Tablet',
        ], [
            'Idempotency-Key' => 'device-enroll-key',
        ]);

    $enrollResponse
        ->assertCreated()
        ->assertJsonPath('data.device_name', 'Operations Tablet');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/security/status')
        ->assertSuccessful()
        ->assertJsonPath('data.mfa_enabled', true)
        ->assertJsonCount(1, 'data.trusted_devices');
});

it('denies configuration reads for privileged users without elevated session proof', function () {
    $user = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    TrustedDevice::query()->create([
        'user_id' => $user->id,
        'device_fingerprint' => 'device-fingerprint-1234567890',
        'device_name' => 'Field Tablet',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/configurations')
        ->assertForbidden()
        ->assertJsonPath('code', 'privileged_session_required');
});
