<?php

use App\Enums\ApprovalEventType;
use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Http\Middleware\RequireWebPrivilegedSession;
use App\Models\ApprovalEvent;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MemberAssignment;
use App\Models\Role;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests to the admin login page', function (): void {
    $this->get('/admin')->assertRedirect(route('login'));
});

it('allows an admin to sign in and redirects privileged roles to security activation', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
        'mfa_enabled' => true,
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => 1,
    ]);

    $response = $this->post('/admin/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.security.show'));
    $this->assertAuthenticatedAs($admin);
});

it('blocks non-admin users from the admin portal', function (): void {
    $member = User::factory()->member()->create();

    $response = $this->actingAs($member)->get('/admin');

    $response->assertForbidden();
});

it('redirects privileged admins to the security screen until the session is elevated', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => 1,
    ]);

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertRedirect(route('admin.security.show'));
});

it('renders the dashboard once a privileged admin has an active trusted session', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'Admin Web Coop',
        'code' => 'AWC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $trustedDevice = TrustedDevice::query()->create([
        'user_id' => $admin->id,
        'device_fingerprint' => 'browser-device-001',
        'device_name' => 'Ops Browser',
    ]);

    $response = $this
        ->actingAs($admin)
        ->withSession([
            RequireWebPrivilegedSession::SESSION_MFA_KEY => true,
            RequireWebPrivilegedSession::SESSION_TRUSTED_DEVICE_KEY => $trustedDevice->id,
        ])
        ->get('/admin');

    $response
        ->assertSuccessful()
        ->assertSee('Dashboard')
        ->assertSee('Active cooperatives');
});

it('approves a pending member assignment from the web admin queue', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'Queue Coop',
        'code' => 'QUE-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $fromCluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'North Cluster',
        'status' => 'active',
    ]);

    $toCluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'South Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $fromCluster->id,
        'member_number' => 'MBR-1001',
        'first_name' => 'Amina',
        'last_name' => 'Bako',
        'status' => 'active',
    ]);

    $assignment = MemberAssignment::query()->create([
        'member_id' => $member->id,
        'cooperative_id' => $cooperative->id,
        'from_cluster_id' => $fromCluster->id,
        'to_cluster_id' => $toCluster->id,
        'assigned_by_user_id' => User::factory()->create(['role' => UserRole::ClusterSupervisor])->id,
        'reason' => 'Route workload rebalance',
        'approval_status' => 'pending_approval',
        'correlation_id' => (string) str()->uuid(),
    ]);

    ApprovalEvent::query()->create([
        'entity_type' => 'member_assignment',
        'entity_id' => $assignment->id,
        'event_type' => ApprovalEventType::Submitted,
        'current_status' => ApprovalStatus::PendingApproval,
        'actor_id' => $assignment->assigned_by_user_id,
        'actor_role' => UserRole::ClusterSupervisor->value,
        'metadata' => [
            'member_id' => $member->id,
            'from_cluster_id' => $fromCluster->id,
            'to_cluster_id' => $toCluster->id,
        ],
        'correlation_id' => $assignment->correlation_id,
    ]);

    $trustedDevice = TrustedDevice::query()->create([
        'user_id' => $admin->id,
        'device_fingerprint' => 'browser-device-approve',
        'device_name' => 'Ops Browser',
    ]);

    $response = $this
        ->actingAs($admin)
        ->withSession([
            RequireWebPrivilegedSession::SESSION_MFA_KEY => true,
            RequireWebPrivilegedSession::SESSION_TRUSTED_DEVICE_KEY => $trustedDevice->id,
        ])
        ->post(route('admin.assignments.approve', $assignment));

    $response->assertRedirect();

    expect($assignment->fresh()->approval_status)->toBe('approved');
    expect($member->fresh()->cluster_id)->toBe($toCluster->id);
});

it('renders assignment approval detail for scoped admin reviewers', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'History Detail Coop',
        'code' => 'HDC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $fromCluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'River Cluster',
        'status' => 'active',
    ]);

    $toCluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Hill Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $fromCluster->id,
        'member_number' => 'HDC-200',
        'first_name' => 'Ibrahim',
        'last_name' => 'Sani',
        'status' => 'active',
    ]);

    $assignment = MemberAssignment::query()->create([
        'member_id' => $member->id,
        'cooperative_id' => $cooperative->id,
        'from_cluster_id' => $fromCluster->id,
        'to_cluster_id' => $toCluster->id,
        'assigned_by_user_id' => User::factory()->create(['role' => UserRole::ClusterSupervisor])->id,
        'reason' => 'Seasonal transfer for capacity balancing',
        'approval_status' => 'approved',
        'approved_by_user_id' => $admin->id,
        'approved_at' => now(),
        'correlation_id' => (string) str()->uuid(),
    ]);

    ApprovalEvent::query()->create([
        'entity_type' => 'member_assignment',
        'entity_id' => $assignment->id,
        'event_type' => ApprovalEventType::Submitted,
        'current_status' => ApprovalStatus::PendingApproval,
        'actor_id' => $assignment->assigned_by_user_id,
        'actor_role' => UserRole::ClusterSupervisor->value,
        'metadata' => [
            'member_id' => $member->id,
            'from_cluster_id' => $fromCluster->id,
            'to_cluster_id' => $toCluster->id,
        ],
        'correlation_id' => $assignment->correlation_id,
    ]);

    ApprovalEvent::query()->create([
        'entity_type' => 'member_assignment',
        'entity_id' => $assignment->id,
        'event_type' => ApprovalEventType::Approved,
        'current_status' => ApprovalStatus::Approved,
        'actor_id' => $admin->id,
        'actor_role' => $admin->role->value,
        'metadata' => [
            'member_id' => $member->id,
            'to_cluster_id' => $toCluster->id,
        ],
        'correlation_id' => $assignment->correlation_id,
    ]);

    $trustedDevice = TrustedDevice::query()->create([
        'user_id' => $admin->id,
        'device_fingerprint' => 'browser-device-history-detail',
        'device_name' => 'Ops Browser',
    ]);

    $response = $this
        ->actingAs($admin)
        ->withSession([
            RequireWebPrivilegedSession::SESSION_MFA_KEY => true,
            RequireWebPrivilegedSession::SESSION_TRUSTED_DEVICE_KEY => $trustedDevice->id,
        ])
        ->get(route('admin.assignments.history.show', $assignment));

    $response
        ->assertSuccessful()
        ->assertSee('Approval Detail')
        ->assertSee('Ibrahim Sani')
        ->assertSee('Approval timeline')
        ->assertSee('River Cluster')
        ->assertSee('Hill Cluster');
});
