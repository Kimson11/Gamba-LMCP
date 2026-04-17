<?php

use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows privileged users to create cooperatives', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/cooperatives', [
        'name' => 'Northern Producers Cooperative',
        'code' => 'npc-01',
        'country_code' => 'ng',
        'status' => 'active',
    ], [
        'Idempotency-Key' => 'cooperative-create-npc-01',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.code', 'NPC-01')
        ->assertJsonPath('data.country_code', 'NG');

    expect(Cooperative::query()->count())->toBe(1);
});

it('denies non-governance users from creating cooperatives', function (): void {
    $member = User::factory()->member()->create();

    $response = $this->actingAs($member, 'sanctum')->postJson('/api/v1/cooperatives', [
        'name' => 'Unauthorized Cooperative',
        'code' => 'unauth-01',
        'country_code' => 'NG',
    ], [
        'Idempotency-Key' => 'cooperative-create-unauth-01',
    ]);

    $response
        ->assertForbidden()
        ->assertJsonPath('code', 'permission_denied');
});

it('creates clusters and lists them by cooperative', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Milk Valley',
        'code' => 'MV-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/clusters', [
        'cooperative_id' => $cooperative->id,
        'name' => 'Cluster A',
        'code' => 'A-01',
    ], [
        'Idempotency-Key' => 'cluster-create-a-01',
    ])->assertCreated();

    $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/cooperatives/{$cooperative->id}/clusters");

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.name', 'Cluster A')
        ->assertJsonPath('data.items.0.cooperative_id', $cooperative->id);
});

it('rejects member creation when cluster and cooperative do not match', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperativeOne = Cooperative::query()->create([
        'name' => 'West Coop',
        'code' => 'WC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cooperativeTwo = Cooperative::query()->create([
        'name' => 'East Coop',
        'code' => 'EC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperativeOne->id,
        'name' => 'Mismatch Cluster',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/members', [
        'cooperative_id' => $cooperativeTwo->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'M-100',
        'first_name' => 'Amina',
        'last_name' => 'Bello',
    ], [
        'Idempotency-Key' => 'member-create-m-100',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed');
});

it('creates and lists members by cluster', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'River Cooperative',
        'code' => 'RC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Cluster West',
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/members', [
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'MEM-001',
        'first_name' => 'Fatima',
        'last_name' => 'Usman',
        'phone' => '+2348000000001',
    ], [
        'Idempotency-Key' => 'member-create-mem-001',
    ])->assertCreated();

    $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/clusters/{$cluster->id}/members");

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.member_number', 'MEM-001')
        ->assertJsonPath('data.items.0.cluster_id', $cluster->id);

    expect(Member::query()->count())->toBe(1);
});
