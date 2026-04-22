<?php

use App\Models\AuditLog;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MemberAssignment;
use App\Models\Role;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows governance role to assign member to another cluster in same cooperative', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Assignment Coop',
        'code' => 'AS-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $clusterOne = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Cluster One',
        'status' => 'active',
    ]);

    $clusterTwo = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Cluster Two',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $clusterOne->id,
        'member_number' => 'AS-100',
        'first_name' => 'Musa',
        'last_name' => 'Idris',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/members/{$member->id}/assign-cluster", [
        'cluster_id' => $clusterTwo->id,
        'reason' => 'Balancing member load across clusters.',
    ], [
        'Idempotency-Key' => 'member-assign-as-100',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.member_id', $member->id)
        ->assertJsonPath('data.from_cluster_id', $clusterOne->id)
        ->assertJsonPath('data.to_cluster_id', $clusterTwo->id)
        ->assertJsonPath('data.approval_status', 'pending_approval');

    expect($member->fresh()->cluster_id)->toBe($clusterOne->id);
    expect(MemberAssignment::query()->count())->toBe(1);
    expect(AuditLog::query()->where('action', 'member_assignment.submitted')->count())->toBe(1);
});

it('approves pending assignment and applies cluster change', function (): void {
    $requester = User::factory()->coopAdmin()->create();
    $reviewer = User::factory()->systemAdmin()->create([
        'mfa_enabled' => true,
    ]);
    $trustedDevice = TrustedDevice::query()->create([
        'user_id' => $reviewer->id,
        'device_fingerprint' => 'member-assignment-review-device-150',
        'device_name' => 'Reviewer Console',
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'Approve Coop',
        'code' => 'APP-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $requester->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $clusterOne = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Before Approval',
        'status' => 'active',
    ]);

    $clusterTwo = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'After Approval',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $clusterOne->id,
        'member_number' => 'AS-150',
        'first_name' => 'Kamilu',
        'last_name' => 'Rabiu',
        'status' => 'active',
    ]);

    $submitted = $this->actingAs($requester, 'sanctum')->postJson("/api/v1/members/{$member->id}/assign-cluster", [
        'cluster_id' => $clusterTwo->id,
    ], [
        'Idempotency-Key' => 'member-assign-submit-as-150',
    ])->assertCreated();

    $assignmentId = (int) $submitted->json('data.id');

    $approved = $this->actingAs($reviewer, 'sanctum')->postJson("/api/v1/member-assignments/{$assignmentId}/approve", [], [
        'Idempotency-Key' => 'member-assign-approve-as-150',
        'X-MFA-Verified' => 'true',
        'X-Trusted-Device-Id' => (string) $trustedDevice->id,
    ]);

    $approved
        ->assertSuccessful()
        ->assertJsonPath('data.approval_status', 'approved');

    expect($member->fresh()->cluster_id)->toBe($clusterTwo->id);
    expect(AuditLog::query()->where('action', 'member_assignment.approved')->count())->toBe(1);

    $audit = AuditLog::query()->where('action', 'member_assignment.approved')->latest('id')->first();

    expect($audit)->not->toBeNull();
    expect($audit->context['submitted_by_user_id'])->toBe($requester->id);
    expect($audit->context['reviewed_by_user_id'])->toBe($reviewer->id);
    expect($audit->context['decision_latency_seconds'])->toBeInt();
});

it('rejects pending assignment with reason and keeps member cluster unchanged', function (): void {
    $requester = User::factory()->coopAdmin()->create();
    $reviewer = User::factory()->systemAdmin()->create([
        'mfa_enabled' => true,
    ]);
    $trustedDevice = TrustedDevice::query()->create([
        'user_id' => $reviewer->id,
        'device_fingerprint' => 'member-assignment-review-device-175',
        'device_name' => 'Reviewer Console',
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'Reject Coop',
        'code' => 'REJ-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $requester->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $clusterOne = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Original Cluster',
        'status' => 'active',
    ]);

    $clusterTwo = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Rejected Target',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $clusterOne->id,
        'member_number' => 'AS-175',
        'first_name' => 'Hauwa',
        'last_name' => 'Danladi',
        'status' => 'active',
    ]);

    $submitted = $this->actingAs($requester, 'sanctum')->postJson("/api/v1/members/{$member->id}/assign-cluster", [
        'cluster_id' => $clusterTwo->id,
    ], [
        'Idempotency-Key' => 'member-assign-submit-as-175',
    ])->assertCreated();

    $assignmentId = (int) $submitted->json('data.id');

    $rejected = $this->actingAs($reviewer, 'sanctum')->postJson("/api/v1/member-assignments/{$assignmentId}/reject", [
        'reason' => 'Insufficient operational justification for reassignment.',
    ], [
        'Idempotency-Key' => 'member-assign-reject-as-175',
        'X-MFA-Verified' => 'true',
        'X-Trusted-Device-Id' => (string) $trustedDevice->id,
    ]);

    $rejected
        ->assertSuccessful()
        ->assertJsonPath('data.approval_status', 'rejected');

    expect($member->fresh()->cluster_id)->toBe($clusterOne->id);

    $audit = AuditLog::query()->where('action', 'member_assignment.rejected')->latest('id')->first();

    expect($audit)->not->toBeNull();
    expect($audit->context['submitted_by_user_id'])->toBe($requester->id);
    expect($audit->context['reviewed_by_user_id'])->toBe($reviewer->id);
    expect($audit->context['decision_latency_seconds'])->toBeInt();
});

it('blocks self-approval for maker-checker separation', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);
    $trustedDevice = TrustedDevice::query()->create([
        'user_id' => $admin->id,
        'device_fingerprint' => 'member-assignment-self-review-device-approve',
        'device_name' => 'Admin Console',
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'SoD Approve Coop',
        'code' => 'SDA-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $clusterOne = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Requester Cluster',
        'status' => 'active',
    ]);

    $clusterTwo = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Target Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $clusterOne->id,
        'member_number' => 'SOD-100',
        'first_name' => 'Mariam',
        'last_name' => 'Bashir',
        'status' => 'active',
    ]);

    $submitted = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/members/{$member->id}/assign-cluster", [
        'cluster_id' => $clusterTwo->id,
    ], [
        'Idempotency-Key' => 'member-assign-sod-approve-submit',
    ])->assertCreated();

    $assignmentId = (int) $submitted->json('data.id');

    $approve = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/member-assignments/{$assignmentId}/approve", [], [
        'Idempotency-Key' => 'member-assign-sod-approve-action',
        'X-MFA-Verified' => 'true',
        'X-Trusted-Device-Id' => (string) $trustedDevice->id,
    ]);

    $approve
        ->assertForbidden()
        ->assertJsonPath('code', 'permission_denied');

    expect($member->fresh()->cluster_id)->toBe($clusterOne->id);
});

it('blocks self-rejection for maker-checker separation', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);
    $trustedDevice = TrustedDevice::query()->create([
        'user_id' => $admin->id,
        'device_fingerprint' => 'member-assignment-self-review-device-reject',
        'device_name' => 'Admin Console',
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'SoD Reject Coop',
        'code' => 'SDR-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $clusterOne = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Requester Cluster',
        'status' => 'active',
    ]);

    $clusterTwo = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Target Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $clusterOne->id,
        'member_number' => 'SOD-101',
        'first_name' => 'Bilkisu',
        'last_name' => 'Ahmad',
        'status' => 'active',
    ]);

    $submitted = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/members/{$member->id}/assign-cluster", [
        'cluster_id' => $clusterTwo->id,
    ], [
        'Idempotency-Key' => 'member-assign-sod-reject-submit',
    ])->assertCreated();

    $assignmentId = (int) $submitted->json('data.id');

    $reject = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/member-assignments/{$assignmentId}/reject", [
        'reason' => 'Self-decision should be blocked by governance rules.',
    ], [
        'Idempotency-Key' => 'member-assign-sod-reject-action',
        'X-MFA-Verified' => 'true',
        'X-Trusted-Device-Id' => (string) $trustedDevice->id,
    ]);

    $reject
        ->assertForbidden()
        ->assertJsonPath('code', 'permission_denied');
});

it('rejects assignment when target cluster belongs to another cooperative', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $coopA = Cooperative::query()->create([
        'name' => 'Coop A',
        'code' => 'CAA-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $coopA->id,
    ]);

    $coopB = Cooperative::query()->create([
        'name' => 'Coop B',
        'code' => 'CBB-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $clusterA = Cluster::query()->create([
        'cooperative_id' => $coopA->id,
        'name' => 'A1',
        'status' => 'active',
    ]);

    $clusterB = Cluster::query()->create([
        'cooperative_id' => $coopB->id,
        'name' => 'B1',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $coopA->id,
        'cluster_id' => $clusterA->id,
        'member_number' => 'AS-200',
        'first_name' => 'Halima',
        'last_name' => 'Lawal',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/members/{$member->id}/assign-cluster", [
        'cluster_id' => $clusterB->id,
    ], [
        'Idempotency-Key' => 'member-assign-as-200',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed');
});

it('denies non-governance role from assigning members', function (): void {
    $memberActor = User::factory()->member()->create();
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Role Guard Coop',
        'code' => 'RGC-01',
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
        'name' => 'Cluster RG',
        'status' => 'active',
    ]);

    $targetCluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Cluster RG 2',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'AS-300',
        'first_name' => 'Aisha',
        'last_name' => 'Yakubu',
        'status' => 'active',
    ]);

    $response = $this->actingAs($memberActor, 'sanctum')->postJson("/api/v1/members/{$member->id}/assign-cluster", [
        'cluster_id' => $targetCluster->id,
    ], [
        'Idempotency-Key' => 'member-assign-as-300',
    ]);

    $response
        ->assertForbidden()
        ->assertJsonPath('code', 'permission_denied');
});

it('lists assignment timeline for a member', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Timeline Coop',
        'code' => 'TLC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $clusterA = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Timeline A',
        'status' => 'active',
    ]);

    $clusterB = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Timeline B',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $clusterA->id,
        'member_number' => 'AS-400',
        'first_name' => 'Nafisa',
        'last_name' => 'Sani',
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/members/{$member->id}/assign-cluster", [
        'cluster_id' => $clusterB->id,
        'reason' => 'Moved after seasonal reassignment.',
    ], [
        'Idempotency-Key' => 'member-assign-as-400',
    ])->assertCreated();

    $history = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/members/{$member->id}/assignments");

    $history
        ->assertSuccessful()
        ->assertJsonPath('data.member_id', $member->id)
        ->assertJsonPath('data.items.0.to_cluster_id', $clusterB->id)
        ->assertJsonPath('data.items.0.reason', 'Moved after seasonal reassignment.')
        ->assertJsonPath('data.items.0.approval_status', 'pending_approval');
});

it('filters assignment history by cooperative and country', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $ngCooperative = Cooperative::query()->create([
        'name' => 'Nigeria Coop',
        'code' => 'NGC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $frCooperative = Cooperative::query()->create([
        'name' => 'France Coop',
        'code' => 'FRC-01',
        'country_code' => 'FR',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $ngCooperative->id,
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $frCooperative->id,
    ]);

    $ngClusterOne = Cluster::query()->create([
        'cooperative_id' => $ngCooperative->id,
        'name' => 'NG-A',
        'status' => 'active',
    ]);

    $ngClusterTwo = Cluster::query()->create([
        'cooperative_id' => $ngCooperative->id,
        'name' => 'NG-B',
        'status' => 'active',
    ]);

    $frClusterOne = Cluster::query()->create([
        'cooperative_id' => $frCooperative->id,
        'name' => 'FR-A',
        'status' => 'active',
    ]);

    $frClusterTwo = Cluster::query()->create([
        'cooperative_id' => $frCooperative->id,
        'name' => 'FR-B',
        'status' => 'active',
    ]);

    $memberNg = Member::query()->create([
        'cooperative_id' => $ngCooperative->id,
        'cluster_id' => $ngClusterOne->id,
        'member_number' => 'AS-450',
        'first_name' => 'Binta',
        'last_name' => 'Musa',
        'status' => 'active',
    ]);

    $memberFr = Member::query()->create([
        'cooperative_id' => $frCooperative->id,
        'cluster_id' => $frClusterOne->id,
        'member_number' => 'AS-451',
        'first_name' => 'Claire',
        'last_name' => 'Martin',
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/members/{$memberNg->id}/assign-cluster", [
        'cluster_id' => $ngClusterTwo->id,
    ], [
        'Idempotency-Key' => 'member-assign-history-ng',
    ])->assertCreated();

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/members/{$memberFr->id}/assign-cluster", [
        'cluster_id' => $frClusterTwo->id,
    ], [
        'Idempotency-Key' => 'member-assign-history-fr',
    ])->assertCreated();

    $history = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/member-assignments/history?country_code=NG&cooperative_id='.$ngCooperative->id);

    $history
        ->assertSuccessful()
        ->assertJsonPath('data.pagination.total', 1)
        ->assertJsonPath('data.items.0.cooperative_id', $ngCooperative->id);
});

it('lists pending assignment queue with filters', function (): void {
    $admin = User::factory()->coopAdmin()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Pending Queue Coop',
        'code' => 'PQC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => $cooperative->id,
    ]);

    $clusterOne = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Queue A',
        'status' => 'active',
    ]);

    $clusterTwo = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Queue B',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $clusterOne->id,
        'member_number' => 'PQ-100',
        'first_name' => 'Hajara',
        'last_name' => 'Tijjani',
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/members/{$member->id}/assign-cluster", [
        'cluster_id' => $clusterTwo->id,
    ], [
        'Idempotency-Key' => 'member-assign-pending-queue',
    ])->assertCreated();

    $pending = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/member-assignments/pending?cooperative_id='.$cooperative->id.'&assigned_by_user_id='.$admin->id);

    $pending
        ->assertSuccessful()
        ->assertJsonPath('data.pagination.total', 1)
        ->assertJsonPath('data.items.0.approval_status', 'pending_approval')
        ->assertJsonPath('data.items.0.assigned_by_user_id', $admin->id);
});
