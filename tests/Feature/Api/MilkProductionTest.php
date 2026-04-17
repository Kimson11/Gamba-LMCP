<?php

use App\Models\AuditLog;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MilkProductionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows governance role to record milk production log', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Production Coop',
        'code' => 'PRD-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Morning Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'MP-100',
        'first_name' => 'Aliyu',
        'last_name' => 'Bello',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/milk-production-logs', [
        'member_id' => $member->id,
        'cluster_id' => $cluster->id,
        'quantity_liters' => 22.5,
        'production_date' => '2026-04-17',
        'source' => 'mobile',
    ], [
        'Idempotency-Key' => 'milk-log-create-1',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.member_id', $member->id)
        ->assertJsonPath('data.cluster_id', $cluster->id)
        ->assertJsonPath('data.cooperative_id', $cooperative->id)
        ->assertJsonPath('data.quantity_liters', 22.5)
        ->assertJsonPath('data.status', 'recorded');

    expect(MilkProductionLog::query()->count())->toBe(1);
    expect(AuditLog::query()->where('action', 'milk_production.recorded')->count())->toBe(1);
});

it('rejects mismatched member and cluster combination', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Validation Coop',
        'code' => 'VAL-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $clusterOne = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Assigned Cluster',
        'status' => 'active',
    ]);

    $clusterTwo = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Wrong Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $clusterOne->id,
        'member_number' => 'MP-120',
        'first_name' => 'Rashida',
        'last_name' => 'Umar',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/milk-production-logs', [
        'member_id' => $member->id,
        'cluster_id' => $clusterTwo->id,
        'quantity_liters' => 10,
        'production_date' => '2026-04-17',
    ], [
        'Idempotency-Key' => 'milk-log-create-2',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.cluster_id.0', 'The selected cluster does not match the member assignment.');
});

it('lists milk production logs by cluster', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'List Coop',
        'code' => 'LST-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'List Cluster',
        'status' => 'active',
    ]);

    $otherCluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Other Cluster',
        'status' => 'active',
    ]);

    $memberOne = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'MP-200',
        'first_name' => 'Zainab',
        'last_name' => 'Sani',
        'status' => 'active',
    ]);

    $memberTwo = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $otherCluster->id,
        'member_number' => 'MP-201',
        'first_name' => 'Ifeoma',
        'last_name' => 'Okeke',
        'status' => 'active',
    ]);

    MilkProductionLog::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_id' => $memberOne->id,
        'recorded_by_user_id' => $admin->id,
        'quantity_liters' => 14.5,
        'production_date' => '2026-04-16',
        'source' => 'mobile',
        'status' => 'recorded',
    ]);

    MilkProductionLog::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $otherCluster->id,
        'member_id' => $memberTwo->id,
        'recorded_by_user_id' => $admin->id,
        'quantity_liters' => 9.0,
        'production_date' => '2026-04-16',
        'source' => 'mobile',
        'status' => 'recorded',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/clusters/{$cluster->id}/milk-production-logs");

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.cluster_id', $cluster->id)
        ->assertJsonPath('data.items.0.cluster_id', $cluster->id);

    expect(count($response->json('data.items')))->toBe(1);
});

it('returns daily milk totals for a cluster', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Cluster Totals Coop',
        'code' => 'CTC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Totals Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'MP-300',
        'first_name' => 'Lami',
        'last_name' => 'Kabir',
        'status' => 'active',
    ]);

    MilkProductionLog::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_id' => $member->id,
        'recorded_by_user_id' => $admin->id,
        'quantity_liters' => 10.5,
        'production_date' => '2026-04-15',
        'source' => 'mobile',
        'status' => 'recorded',
    ]);

    MilkProductionLog::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_id' => $member->id,
        'recorded_by_user_id' => $admin->id,
        'quantity_liters' => 9.0,
        'production_date' => '2026-04-15',
        'source' => 'mobile',
        'status' => 'recorded',
    ]);

    MilkProductionLog::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_id' => $member->id,
        'recorded_by_user_id' => $admin->id,
        'quantity_liters' => 7.5,
        'production_date' => '2026-04-16',
        'source' => 'mobile',
        'status' => 'recorded',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/clusters/{$cluster->id}/milk-production-totals/daily?from_date=2026-04-15&to_date=2026-04-16");

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.cluster_id', $cluster->id)
        ->assertJsonPath('data.items.0.production_date', '2026-04-15')
        ->assertJsonPath('data.items.0.entries_count', 2)
        ->assertJsonPath('data.items.0.total_quantity_liters', 19.5)
        ->assertJsonPath('data.items.1.production_date', '2026-04-16')
        ->assertJsonPath('data.items.1.entries_count', 1)
        ->assertJsonPath('data.items.1.total_quantity_liters', 7.5);
});

it('returns daily milk totals for a member', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Member Totals Coop',
        'code' => 'MTC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Member Totals Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'MP-400',
        'first_name' => 'Jamila',
        'last_name' => 'Garba',
        'status' => 'active',
    ]);

    MilkProductionLog::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_id' => $member->id,
        'recorded_by_user_id' => $admin->id,
        'quantity_liters' => 5.0,
        'production_date' => '2026-04-14',
        'source' => 'mobile',
        'status' => 'recorded',
    ]);

    MilkProductionLog::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_id' => $member->id,
        'recorded_by_user_id' => $admin->id,
        'quantity_liters' => 8.5,
        'production_date' => '2026-04-14',
        'source' => 'mobile',
        'status' => 'recorded',
    ]);

    MilkProductionLog::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_id' => $member->id,
        'recorded_by_user_id' => $admin->id,
        'quantity_liters' => 4.5,
        'production_date' => '2026-04-17',
        'source' => 'mobile',
        'status' => 'recorded',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/members/{$member->id}/milk-production-totals/daily?from_date=2026-04-14&to_date=2026-04-17");

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.member_id', $member->id)
        ->assertJsonPath('data.items.0.production_date', '2026-04-14')
        ->assertJsonPath('data.items.0.entries_count', 2)
        ->assertJsonPath('data.items.0.total_quantity_liters', 13.5)
        ->assertJsonPath('data.items.1.production_date', '2026-04-17')
        ->assertJsonPath('data.items.1.entries_count', 1)
        ->assertJsonPath('data.items.1.total_quantity_liters', 4.5);
});
