<?php

use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MemberAssignment;
use App\Models\Role;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function privilegedHeaders(User $user, string $idempotencyKey): array
{
    $trustedDevice = TrustedDevice::query()->create([
        'user_id' => $user->id,
        'device_fingerprint' => 'trusted-device-'.$idempotencyKey,
        'device_name' => 'Operations Tablet',
    ]);

    return [
        'Idempotency-Key' => $idempotencyKey,
        'X-MFA-Verified' => 'true',
        'X-Trusted-Device-Id' => (string) $trustedDevice->id,
    ];
}

it('returns the authenticated session bootstrap contract with scopes and linked member', function (): void {
    $user = User::factory()->member()->create([
        'email' => 'member@example.com',
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'Bootstrap Coop',
        'code' => 'BST-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Bootstrap Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'user_id' => $user->id,
        'member_number' => 'MEM-BST-001',
        'first_name' => 'Amina',
        'last_name' => 'Bello',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.user.email', 'member@example.com')
        ->assertJsonPath('data.user.role', 'member')
        ->assertJsonPath('data.scopes.member_id', $member->id)
        ->assertJsonPath('data.scopes.cooperative_ids.0', $cooperative->id)
        ->assertJsonPath('data.linked_member.member_number', 'MEM-BST-001');
});

it('returns the member home summary contract with recent activity and notifications', function (): void {
    $user = User::factory()->member()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Member Home Coop',
        'code' => 'MHC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Member Home Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'user_id' => $user->id,
        'member_number' => 'MEM-HOME-001',
        'first_name' => 'Zainab',
        'last_name' => 'Musa',
        'status' => 'active',
    ]);

    app(NotificationDispatcher::class)->sendDatabase(
        recipient: $user,
        type: 'sync.failed',
        title: 'Sync Failed',
        message: 'Retry your last sync.',
        payload: ['queue_size' => 2],
    );

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/milk-production-logs', [
        'member_id' => $member->id,
        'cluster_id' => $cluster->id,
        'quantity_liters' => 12.5,
        'production_date' => now()->toDateString(),
    ], [
        'Idempotency-Key' => 'member-home-milk-log',
    ])->assertCreated();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/member-home');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.member.id', $member->id)
        ->assertJsonPath('data.kpis.today_quantity_liters', 12.5)
        ->assertJsonPath('data.kpis.unread_notifications', 1)
        ->assertJsonPath('data.recent_activity.0.quantity_liters', 12.5)
        ->assertJsonPath('data.latest_notifications.0.type', 'sync.failed');
});

it('returns the admin dashboard summary with scoped kpis and pending approvals', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'Admin Summary Coop',
        'code' => 'ASC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Admin Summary Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'ADM-001',
        'first_name' => 'Halima',
        'last_name' => 'Sule',
        'status' => 'active',
    ]);

    MemberAssignment::query()->create([
        'member_id' => $member->id,
        'cooperative_id' => $cooperative->id,
        'from_cluster_id' => $cluster->id,
        'to_cluster_id' => $cluster->id,
        'assigned_by_user_id' => $admin->id,
        'approval_status' => 'pending_approval',
        'correlation_id' => 'corr-admin-summary',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->getJson(
        '/api/v1/admin/dashboard-summary',
        privilegedHeaders($admin, 'admin-dashboard-summary')
    );

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.scope_summary.cooperative_ids.0', $cooperative->id)
        ->assertJsonPath('data.kpis.active_members', 1)
        ->assertJsonPath('data.approvals.0.type', 'member_assignments')
        ->assertJsonPath('data.approvals.0.count', 1);
});

it('returns inbox notifications and marks them as read idempotently', function (): void {
    $user = User::factory()->member()->create();

    app(NotificationDispatcher::class)->sendDatabase(
        recipient: $user,
        type: 'approval.pending',
        title: 'Approval Needed',
        message: 'Review the pending request.',
        payload: ['entity_type' => 'member_assignment', 'entity_id' => 44],
    );

    $inbox = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');

    $inbox
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.type', 'approval.pending')
        ->assertJsonPath('data.unread_count', 1);

    $notificationId = (string) $inbox->json('data.items.0.id');

    $markRead = $this->actingAs($user, 'sanctum')->postJson(
        "/api/v1/notifications/{$notificationId}/read",
        [],
        ['Idempotency-Key' => 'notification-read-1']
    );

    $markRead
        ->assertSuccessful()
        ->assertJsonPath('data.id', $notificationId);

    $secondRead = $this->actingAs($user, 'sanctum')->postJson(
        "/api/v1/notifications/{$notificationId}/read",
        [],
        ['Idempotency-Key' => 'notification-read-1']
    );

    $secondRead
        ->assertSuccessful()
        ->assertHeader('X-Idempotency-Replayed', 'true');
});

it('denies cross-scope reads for cooperative-scoped admins', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $allowedCooperative = Cooperative::query()->create([
        'name' => 'Allowed Coop',
        'code' => 'ALW-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $forbiddenCooperative = Cooperative::query()->create([
        'name' => 'Forbidden Coop',
        'code' => 'FRB-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $allowedCooperative->id,
    ]);

    $forbiddenCluster = Cluster::query()->create([
        'cooperative_id' => $forbiddenCooperative->id,
        'name' => 'Forbidden Cluster',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/clusters/{$forbiddenCluster->id}/members");

    $response
        ->assertForbidden()
        ->assertJsonPath('code', 'permission_denied');
});

it('returns zeroed dashboard aggregates when scoped admin has no records', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'Empty Summary Coop',
        'code' => 'ESC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $response = $this->actingAs($admin, 'sanctum')->getJson(
        '/api/v1/admin/dashboard-summary',
        privilegedHeaders($admin, 'admin-dashboard-summary-empty')
    );

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.kpis.active_members', 0)
        ->assertJsonPath('data.kpis.today_milk_volume_liters', 0)
        ->assertJsonPath('data.kpis.seven_day_milk_volume_liters', 0)
        ->assertJsonPath('data.approvals.0.count', 0)
        ->assertJsonPath('data.exceptions.1.count', 0);
});

it('returns empty notification inbox contract for users without notifications', function (): void {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.unread_count', 0)
        ->assertJsonPath('data.pagination.total', 0)
        ->assertJsonPath('data.items', []);
});
