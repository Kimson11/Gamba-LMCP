<?php

/**
 * Audit log feature tests — Phase B.2.3
 *
 * These tests verify that:
 *  1. AuditLogger.record() creates an immutable audit row.
 *  2. The correlation_id from the HTTP request is captured in the row.
 *  3. The actor role is snapshotted at write time.
 *  4. System events (no actor) are supported.
 *  5. The login action produces an audit row (integration with AuthController).
 *
 * All tests use RefreshDatabase for complete isolation.
 */

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── AuditLogger service unit-style tests ────────────────────────────────────

it('records an audit log row with correct actor and action', function () {
    $user = User::factory()->member()->create();

    // Simulate an authenticated request by acting as the user.
    $this->actingAs($user, 'sanctum');

    // Call the service directly to test it in isolation.
    app(AuditLogger::class)->record(
        action: 'milk_log.created',
        context: ['quantity_litres' => 12.5],
    );

    // Assert a single audit row was created with the expected values.
    $log = AuditLog::first();

    expect($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($user->id)
        // Role must be snapshotted as the string value, not the enum.
        ->and($log->actor_role)->toBe(UserRole::Member->value)
        ->and($log->action)->toBe('milk_log.created')
        ->and($log->context)->toBe(['quantity_litres' => 12.5]);
});

it('captures the correlation_id from the request context', function () {
    $user = User::factory()->create();
    $correlationId = (string) str()->uuid();

    // Inject a fake correlation_id into the request attributes,
    // simulating what AttachRequestContext middleware would set.
    request()->attributes->set('correlation_id', $correlationId);

    $this->actingAs($user, 'sanctum');

    app(AuditLogger::class)->record(action: 'payout.approved');

    // The audit row should carry the same correlation_id as the request.
    expect(AuditLog::first()->correlation_id)->toBe($correlationId);
});

it('records a system-level audit event with no actor', function () {
    // Pass actor: null explicitly — represents a scheduled job or system action.
    app(AuditLogger::class)->record(
        action: 'system.daily_aggregation_run',
        actor: null,
    );

    $log = AuditLog::first();

    expect($log->actor_id)->toBeNull()
        ->and($log->actor_role)->toBeNull()
        ->and($log->action)->toBe('system.daily_aggregation_run');
});

it('snapshots the role even if the user role changes after logging', function () {
    // Create a member user and record an audit event.
    $user = User::factory()->member()->create();
    $this->actingAs($user, 'sanctum');

    app(AuditLogger::class)->record(action: 'milk_log.created');

    // Now change the user's role to coopAdmin.
    $user->update(['role' => UserRole::CoopAdmin]);

    // The audit row should still show 'member' — the role at the time of the event.
    expect(AuditLog::first()->actor_role)->toBe(UserRole::Member->value);
});

// ─── Integration: login produces an audit row ─────────────────────────────────

it('creates an audit log entry when a user logs in', function () {
    User::factory()->create([
        'email' => 'audit-test@example.com',
        'password' => bcrypt('password'),
    ]);

    // Perform an actual login request so the AuthController → AuditLogger path runs.
    $this->postJson('/api/v1/auth/login', [
        'email' => 'audit-test@example.com',
        'password' => 'password',
        'device_name' => 'Audit Test Device',
    ])->assertCreated();

    // Verify the audit log was created with the expected action.
    $log = AuditLog::where('action', 'user.login')->first();

    expect($log)->not->toBeNull()
        ->and($log->context['device_name'])->toBe('Audit Test Device');
});
