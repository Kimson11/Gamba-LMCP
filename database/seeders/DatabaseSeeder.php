<?php

namespace Database\Seeders;

use App\Enums\ApprovalEventType;
use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\ApprovalEvent;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MemberAssignment;
use App\Models\MilkProductionLog;
use App\Models\NotificationDelivery;
use App\Models\Role;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $demoCooperative = Cooperative::query()->updateOrCreate([
            'code' => 'DEMO-COOP',
        ], [
            'name' => 'Demo Cooperative Union',
            'country_code' => 'NG',
            'status' => 'active',
        ]);

        $northCooperative = Cooperative::query()->updateOrCreate([
            'code' => 'NORTH-COOP',
        ], [
            'name' => 'Northern Dairy Cooperative',
            'country_code' => 'NG',
            'status' => 'active',
        ]);

        User::query()->updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'System Administrator',
            'password' => 'password',
            'role' => UserRole::SystemAdmin,
            'mfa_enabled' => true,
            'mfa_secret_encrypted' => encrypt('seeded-bootstrap-admin-secret'),
            'mfa_secret_rotated_at' => now(),
        ]);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        TrustedDevice::query()->updateOrCreate([
            'user_id' => $admin->id,
            'device_fingerprint' => 'seeded-admin-browser',
        ], [
            'device_name' => 'Seeded Admin Browser',
            'last_used_at' => now(),
            'expires_at' => null,
            'revoked_at' => null,
        ]);

        User::query()->updateOrCreate([
            'email' => 'coopadmin@example.com',
        ], [
            'name' => 'Demo Cooperative Admin',
            'password' => 'password',
            'role' => UserRole::CoopAdmin,
            'mfa_enabled' => true,
            'mfa_secret_encrypted' => encrypt('seeded-demo-coop-admin-secret'),
            'mfa_secret_rotated_at' => now(),
        ]);

        $coopAdmin = User::query()->where('email', 'coopadmin@example.com')->firstOrFail();

        Role::query()->updateOrCreate([
            'user_id' => $coopAdmin->id,
            'scope_type' => 'cooperative',
            'scope_id' => $demoCooperative->id,
        ], []);

        TrustedDevice::query()->updateOrCreate([
            'user_id' => $coopAdmin->id,
            'device_fingerprint' => 'seeded-coop-admin-browser',
        ], [
            'device_name' => 'Seeded Coop Admin Browser',
            'last_used_at' => now(),
            'expires_at' => null,
            'revoked_at' => null,
        ]);

        $countryAdmin = User::query()->updateOrCreate([
            'email' => 'countryadmin@example.com',
        ], [
            'name' => 'Country Operations Admin',
            'password' => 'password',
            'role' => UserRole::CountryAdmin,
            'mfa_enabled' => true,
            'mfa_secret_encrypted' => encrypt('seeded-country-admin-secret'),
            'mfa_secret_rotated_at' => now(),
        ]);

        Role::query()->updateOrCreate([
            'user_id' => $countryAdmin->id,
            'scope_type' => 'cooperative',
            'scope_id' => $demoCooperative->id,
        ], []);

        Role::query()->updateOrCreate([
            'user_id' => $countryAdmin->id,
            'scope_type' => 'cooperative',
            'scope_id' => $northCooperative->id,
        ], []);

        User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
        ]);

        $memberUser = User::query()->updateOrCreate([
            'email' => 'member@example.com',
        ], [
            'name' => 'Demo Member User',
            'password' => 'password',
            'role' => UserRole::Member,
        ]);

        $supervisor = User::query()->updateOrCreate([
            'email' => 'supervisor@example.com',
        ], [
            'name' => 'Cluster Supervisor',
            'password' => 'password',
            'role' => UserRole::ClusterSupervisor,
        ]);

        $demoClusters = [
            Cluster::query()->updateOrCreate([
                'cooperative_id' => $demoCooperative->id,
                'code' => 'DEMO-C1',
            ], [
                'name' => 'Demo Central Cluster',
                'supervisor_user_id' => $supervisor->id,
                'status' => 'active',
            ]),
            Cluster::query()->updateOrCreate([
                'cooperative_id' => $demoCooperative->id,
                'code' => 'DEMO-C2',
            ], [
                'name' => 'Demo River Cluster',
                'supervisor_user_id' => $supervisor->id,
                'status' => 'active',
            ]),
        ];

        $northClusters = [
            Cluster::query()->updateOrCreate([
                'cooperative_id' => $northCooperative->id,
                'code' => 'NORTH-C1',
            ], [
                'name' => 'North Plains Cluster',
                'supervisor_user_id' => $supervisor->id,
                'status' => 'active',
            ]),
            Cluster::query()->updateOrCreate([
                'cooperative_id' => $northCooperative->id,
                'code' => 'NORTH-C2',
            ], [
                'name' => 'North Hills Cluster',
                'supervisor_user_id' => $supervisor->id,
                'status' => 'active',
            ]),
        ];

        $members = [
            Member::query()->updateOrCreate([
                'cooperative_id' => $demoCooperative->id,
                'member_number' => 'DM-001',
            ], [
                'cluster_id' => $demoClusters[0]->id,
                'user_id' => $memberUser->id,
                'first_name' => 'Amina',
                'last_name' => 'Bello',
                'phone' => '+2348000000101',
                'status' => 'active',
            ]),
            Member::query()->updateOrCreate([
                'cooperative_id' => $demoCooperative->id,
                'member_number' => 'DM-002',
            ], [
                'cluster_id' => $demoClusters[0]->id,
                'first_name' => 'Musa',
                'last_name' => 'Sani',
                'phone' => '+2348000000102',
                'status' => 'active',
            ]),
            Member::query()->updateOrCreate([
                'cooperative_id' => $demoCooperative->id,
                'member_number' => 'DM-003',
            ], [
                'cluster_id' => $demoClusters[1]->id,
                'first_name' => 'Zainab',
                'last_name' => 'Aliyu',
                'phone' => '+2348000000103',
                'status' => 'active',
            ]),
            Member::query()->updateOrCreate([
                'cooperative_id' => $northCooperative->id,
                'member_number' => 'NM-001',
            ], [
                'cluster_id' => $northClusters[0]->id,
                'first_name' => 'Ibrahim',
                'last_name' => 'Garba',
                'phone' => '+2348000000201',
                'status' => 'active',
            ]),
            Member::query()->updateOrCreate([
                'cooperative_id' => $northCooperative->id,
                'member_number' => 'NM-002',
            ], [
                'cluster_id' => $northClusters[1]->id,
                'first_name' => 'Halima',
                'last_name' => 'Umar',
                'phone' => '+2348000000202',
                'status' => 'active',
            ]),
        ];

        $milkRows = [
            [$members[0], $demoClusters[0], 15.2, 0],
            [$members[0], $demoClusters[0], 14.8, 1],
            [$members[1], $demoClusters[0], 12.1, 0],
            [$members[2], $demoClusters[1], 10.4, 2],
            [$members[3], $northClusters[0], 17.6, 1],
            [$members[4], $northClusters[1], 11.9, 0],
            [$members[4], $northClusters[1], 12.5, 3],
        ];

        foreach ($milkRows as [$member, $cluster, $quantity, $daysAgo]) {
            $productionDate = Carbon::now()->subDays($daysAgo)->toDateString();

            MilkProductionLog::query()->updateOrCreate([
                'member_id' => $member->id,
                'production_date' => $productionDate,
            ], [
                'cooperative_id' => $member->cooperative_id,
                'cluster_id' => $cluster->id,
                'recorded_by_user_id' => $coopAdmin->id,
                'quantity_liters' => $quantity,
                'source' => 'mobile',
                'status' => 'accepted',
            ]);
        }

        $approvedAssignment = MemberAssignment::query()->updateOrCreate([
            'member_id' => $members[2]->id,
            'to_cluster_id' => $demoClusters[0]->id,
            'correlation_id' => '11111111-1111-1111-1111-111111111111',
        ], [
            'cooperative_id' => $demoCooperative->id,
            'from_cluster_id' => $demoClusters[1]->id,
            'assigned_by_user_id' => $supervisor->id,
            'reason' => 'Seasonal route rebalancing',
            'approval_status' => ApprovalStatus::Approved->value,
            'approved_by_user_id' => $coopAdmin->id,
            'approved_at' => now()->subDay(),
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $pendingAssignment = MemberAssignment::query()->updateOrCreate([
            'member_id' => $members[1]->id,
            'to_cluster_id' => $demoClusters[1]->id,
            'correlation_id' => '22222222-2222-2222-2222-222222222222',
        ], [
            'cooperative_id' => $demoCooperative->id,
            'from_cluster_id' => $demoClusters[0]->id,
            'assigned_by_user_id' => $supervisor->id,
            'reason' => 'Capacity balancing',
            'approval_status' => ApprovalStatus::PendingApproval->value,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $rejectedAssignment = MemberAssignment::query()->updateOrCreate([
            'member_id' => $members[3]->id,
            'to_cluster_id' => $northClusters[1]->id,
            'correlation_id' => '33333333-3333-3333-3333-333333333333',
        ], [
            'cooperative_id' => $northCooperative->id,
            'from_cluster_id' => $northClusters[0]->id,
            'assigned_by_user_id' => $supervisor->id,
            'reason' => 'Temporary transfer request',
            'approval_status' => ApprovalStatus::Rejected->value,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejected_by_user_id' => $countryAdmin->id,
            'rejected_at' => now()->subHours(6),
            'rejection_reason' => 'Receiving cluster is currently over capacity.',
        ]);

        $this->seedApprovalTimeline($approvedAssignment, $supervisor, $coopAdmin);
        $this->seedApprovalTimeline($pendingAssignment, $supervisor, null);
        $this->seedApprovalTimeline($rejectedAssignment, $supervisor, $countryAdmin, true);

        $this->seedNotificationForUser(
            recipient: $coopAdmin,
            type: 'approval.pending',
            title: 'Member assignment awaiting review',
            message: 'DM-002 transfer request is pending approval.',
            payload: ['entity_type' => 'member_assignment', 'entity_id' => $pendingAssignment->id],
            status: 'delivered'
        );

        $this->seedNotificationForUser(
            recipient: $memberUser,
            type: 'sync.failed',
            title: 'Sync attention needed',
            message: 'One of your milk logs failed to sync. Open Sync Center to retry.',
            payload: ['queue_size' => 1],
            status: 'failed',
            failureReason: 'Network timeout while posting sync batch.'
        );
    }

    /**
     * Seed append-only approval history for a member assignment.
     */
    private function seedApprovalTimeline(
        MemberAssignment $assignment,
        User $submittedBy,
        ?User $reviewedBy,
        bool $rejected = false,
    ): void {
        ApprovalEvent::query()->updateOrCreate([
            'entity_type' => 'member_assignment',
            'entity_id' => $assignment->id,
            'event_type' => ApprovalEventType::Submitted,
        ], [
            'current_status' => ApprovalStatus::PendingApproval,
            'actor_id' => $submittedBy->id,
            'actor_role' => $submittedBy->role->value,
            'metadata' => ['member_id' => $assignment->member_id],
            'correlation_id' => $assignment->correlation_id,
            'reason' => null,
        ]);

        if ($reviewedBy === null) {
            return;
        }

        $eventType = $rejected ? ApprovalEventType::Rejected : ApprovalEventType::Approved;
        $status = $rejected ? ApprovalStatus::Rejected : ApprovalStatus::Approved;

        ApprovalEvent::query()->updateOrCreate([
            'entity_type' => 'member_assignment',
            'entity_id' => $assignment->id,
            'event_type' => $eventType,
        ], [
            'current_status' => $status,
            'actor_id' => $reviewedBy->id,
            'actor_role' => $reviewedBy->role->value,
            'metadata' => ['member_id' => $assignment->member_id],
            'correlation_id' => $assignment->correlation_id,
            'reason' => $rejected ? $assignment->rejection_reason : null,
        ]);
    }

    /**
     * Seed one database notification and companion delivery ledger row.
     *
     * @param  array<string, mixed>  $payload
     */
    private function seedNotificationForUser(
        User $recipient,
        string $type,
        string $title,
        string $message,
        array $payload,
        string $status,
        ?string $failureReason = null,
    ): void {
        $notificationId = (string) Str::uuid();

        DatabaseNotification::query()->updateOrCreate([
            'id' => $notificationId,
        ], [
            'type' => 'App\\Notifications\\SystemMessageNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'data' => [
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'payload' => $payload,
            ],
            'read_at' => null,
        ]);

        NotificationDelivery::query()->updateOrCreate([
            'notification_id' => $notificationId,
        ], [
            'recipient_user_id' => $recipient->id,
            'notification_type' => $type,
            'channel' => 'database',
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'payload' => $payload,
            'failure_reason' => $failureReason,
            'correlation_id' => (string) Str::uuid(),
            'delivered_at' => $status === 'delivered' ? now() : null,
        ]);
    }
}
