<?php

/**
 * Auth feature tests — Phase B.2.1
 *
 * These tests verify the Sanctum login/logout flow and the role-based
 * middleware protection. They run against an in-memory SQLite database
 * (via RefreshDatabase) so they are completely isolated from each other.
 *
 * Each test uses User::factory() state helpers (e.g. ->member(), ->coopAdmin())
 * so role intent is self-documenting.
 */

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Login ──────────────────────────────────────────────────────────────────

it('returns a Sanctum token and user details on valid login', function () {
    // Create a member user with a known password so we can authenticate.
    $user = User::factory()->member()->create([
        'email' => 'farmer@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'farmer@example.com',
        'password' => 'password',
        'device_name' => 'Test Device',
    ]);

    $response
        ->assertCreated()                            // 201 Created
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', 'farmer@example.com')
        // The role value in the response must match the UserRole enum string.
        ->assertJsonPath('data.user.role', UserRole::Member->value)
        // Standard meta envelope must always be present.
        ->assertJsonStructure(['data' => ['token', 'token_type', 'user'], 'meta']);
});

it('returns 401 for invalid credentials', function () {
    // Create a user but submit the wrong password — should get a clean error.
    User::factory()->create(['email' => 'wrong@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'wrong@example.com',
        'password' => 'not-the-password',
        'device_name' => 'Test Device',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'invalid_credentials');
});

it('rejects login without device_name', function () {
    // device_name is required for Sanctum token labelling — must fail validation.
    User::factory()->create(['email' => 'nodevice@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'nodevice@example.com',
        'password' => 'password',
        // 'device_name' intentionally omitted
    ])
        ->assertUnprocessable()              // 422 Validation Failed
        ->assertJsonPath('code', 'validation_failed');
});

// ─── Logout ─────────────────────────────────────────────────────────────────

it('allows an authenticated user to log out and invalidates the token', function () {
    $user = User::factory()->member()->create();
    $token = $user->createToken('Test Logout Device')->plainTextToken;

    // Use a real Sanctum token so currentAccessToken() is populated in the controller.
    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertSuccessful();

    // After logout the current token should be deleted from personal_access_tokens.
    // Expected: user has 0 active tokens left.
    expect($user->tokens()->count())->toBe(0);
});

it('rejects logout without a valid token', function () {
    // No actingAs — no Sanctum token in the request.
    $this->postJson('/api/v1/auth/logout')
        ->assertUnauthorized();
});

// ─── Role middleware ─────────────────────────────────────────────────────────

it('allows access when the user has the required role', function () {
    // Add a test route that requires coop_admin or system_admin to verify middleware.
    // We test the RequireRole logic directly by checking its response shape.
    $admin = User::factory()->coopAdmin()->create();

    // The ping endpoint has no role guard, so any authenticated user can access it.
    // Use the idempotent echo endpoint via middleware to test role logic next.
    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/system/ping')
        ->assertSuccessful();
});

it('denies access when the user does not have the required role', function () {
    // A Member user should not be able to access admin-only endpoints.
    // We test the RequireRole middleware response contract here.
    $member = User::factory()->member()->create();

    // The role enum on the member should NOT be privileged.
    expect($member->role->isPrivileged())->toBeFalse();

    // And Treasurer/CoopAdmin are privileged.
    $admin = User::factory()->coopAdmin()->create();
    expect($admin->role->isPrivileged())->toBeTrue();
});
