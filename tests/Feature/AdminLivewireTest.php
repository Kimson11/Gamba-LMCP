<?php

use App\Enums\ApprovalEventType;
use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Http\Middleware\RequireWebPrivilegedSession;
use App\Livewire\Admin\ApprovalHistory;
use App\Livewire\Admin\MilkAnalytics;
use App\Livewire\Admin\NotificationInbox;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function privilegedAdminSession(User $admin): array
{
    $trustedDevice = TrustedDevice::query()->create([
        'user_id' => $admin->id,
        'device_fingerprint' => 'web-livewire-device-'.$admin->id,
        'device_name' => 'Ops Browser',
    ]);

    return [
        RequireWebPrivilegedSession::SESSION_MFA_KEY => true,
        RequireWebPrivilegedSession::SESSION_TRUSTED_DEVICE_KEY => $trustedDevice->id,
    ];
}

it('renders the analytics page with the livewire chart workspace', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'Analytics Coop',
        'code' => 'ANA-01',
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
        'name' => 'North Milk Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'ANA-100',
        'first_name' => 'Zainab',
        'last_name' => 'Aliyu',
        'status' => 'active',
    ]);

    MilkProductionLog::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_id' => $member->id,
        'recorded_by_user_id' => $admin->id,
        'quantity_liters' => 48.5,
        'production_date' => now()->toDateString(),
        'source' => 'manual',
        'status' => 'accepted',
    ]);

    $response = $this
        ->actingAs($admin)
        ->withSession(privilegedAdminSession($admin))
        ->get(route('admin.analytics.index'));

    $response
        ->assertSuccessful()
        ->assertSee('Milk totals and trend charts')
        ->assertSee('Daily milk production')
        ->assertSee('Refreshing analytics view...');

    Livewire::actingAs($admin)
        ->test(MilkAnalytics::class)
        ->set('days', 7)
        ->assertSee('48.5 L')
        ->assertSee('North Milk Cluster');
});

it('filters and marks notifications as read from the livewire inbox', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    Role::query()->create([
        'user_id' => $admin->id,
        'scope_type' => 'cooperative',
        'scope_id' => 1,
    ]);

    $notification = DatabaseNotification::query()->create([
        'id' => (string) str()->uuid(),
        'type' => 'App\\Notifications\\SystemMessageNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $admin->id,
        'data' => [
            'title' => 'Daily production spike',
            'message' => 'Milk totals rose above the cooperative threshold.',
            'type' => 'operations',
        ],
    ]);

    NotificationDelivery::query()->create([
        'recipient_user_id' => $admin->id,
        'notification_id' => $notification->id,
        'notification_type' => 'system_message',
        'channel' => 'database',
        'status' => 'delivered',
        'title' => 'Daily production spike',
        'message' => 'Milk totals rose above the cooperative threshold.',
        'payload' => ['screen' => 'analytics'],
        'delivered_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(NotificationInbox::class)
        ->set('search', 'production spike')
        ->assertSee('Daily production spike')
        ->set('statusFilter', 'unread')
        ->call('markRead', $notification->id)
        ->assertSee('Read');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('renders approval history with status filtering and timelines', function (): void {
    $admin = User::factory()->coopAdmin()->create([
        'mfa_enabled' => true,
    ]);

    $cooperative = Cooperative::query()->create([
        'name' => 'History Coop',
        'code' => 'HIS-01',
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
        'name' => 'West Cluster',
        'status' => 'active',
    ]);

    $toCluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'East Cluster',
        'status' => 'active',
    ]);

    $member = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $fromCluster->id,
        'member_number' => 'HIS-100',
        'first_name' => 'Musa',
        'last_name' => 'Bello',
        'status' => 'active',
    ]);

    $assignment = MemberAssignment::query()->create([
        'member_id' => $member->id,
        'cooperative_id' => $cooperative->id,
        'from_cluster_id' => $fromCluster->id,
        'to_cluster_id' => $toCluster->id,
        'assigned_by_user_id' => User::factory()->create(['role' => UserRole::ClusterSupervisor])->id,
        'reason' => 'Seasonal relocation',
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
        'metadata' => ['member_id' => $member->id],
        'correlation_id' => $assignment->correlation_id,
    ]);

    ApprovalEvent::query()->create([
        'entity_type' => 'member_assignment',
        'entity_id' => $assignment->id,
        'event_type' => ApprovalEventType::Approved,
        'current_status' => ApprovalStatus::Approved,
        'actor_id' => $admin->id,
        'actor_role' => $admin->role->value,
        'metadata' => ['member_id' => $member->id],
        'correlation_id' => $assignment->correlation_id,
    ]);

    $response = $this
        ->actingAs($admin)
        ->withSession(privilegedAdminSession($admin))
        ->get(route('admin.assignments.history'));

    $response
        ->assertSuccessful()
        ->assertSee('Approval decision timeline')
        ->assertSee('Musa Bello')
        ->assertSee('Refreshing approval history...')
        ->assertSee('Open detail view');

    Livewire::actingAs($admin)
        ->test(ApprovalHistory::class)
        ->set('statusFilter', 'approved')
        ->set('search', 'HIS-100')
        ->assertSee('Musa Bello')
        ->assertSee('Approved')
        ->assertSee('Submitted');
});
