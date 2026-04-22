<?php

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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds a privileged bootstrap admin account with a trusted device', function (): void {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@example.com')->first();

    expect($admin)->not->toBeNull();
    expect($admin?->role)->toBe(UserRole::SystemAdmin);
    expect($admin?->mfa_enabled)->toBeTrue();
    expect($admin?->role->canAccessAdminPortal())->toBeTrue();

    $trustedDevice = TrustedDevice::query()
        ->where('user_id', $admin?->id)
        ->where('device_fingerprint', 'seeded-admin-browser')
        ->first();

    expect($trustedDevice)->not->toBeNull();
    expect($trustedDevice?->device_name)->toBe('Seeded Admin Browser');
});

it('seeds a scoped cooperative admin demo account with trusted device access', function (): void {
    $this->seed(DatabaseSeeder::class);

    $coopAdmin = User::query()->where('email', 'coopadmin@example.com')->first();
    $demoCooperative = Cooperative::query()->where('code', 'DEMO-COOP')->first();

    expect($coopAdmin)->not->toBeNull();
    expect($demoCooperative)->not->toBeNull();
    expect($coopAdmin?->role)->toBe(UserRole::CoopAdmin);
    expect($coopAdmin?->mfa_enabled)->toBeTrue();

    $scope = Role::query()
        ->where('user_id', $coopAdmin?->id)
        ->where('scope_type', 'cooperative')
        ->where('scope_id', $demoCooperative?->id)
        ->first();

    expect($scope)->not->toBeNull();

    $trustedDevice = TrustedDevice::query()
        ->where('user_id', $coopAdmin?->id)
        ->where('device_fingerprint', 'seeded-coop-admin-browser')
        ->first();

    expect($trustedDevice)->not->toBeNull();
    expect($trustedDevice?->device_name)->toBe('Seeded Coop Admin Browser');
});

it('seeds realistic demo operations data for review workflows', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Cooperative::query()->count())->toBeGreaterThanOrEqual(2);
    expect(Cluster::query()->count())->toBeGreaterThanOrEqual(4);
    expect(Member::query()->count())->toBeGreaterThanOrEqual(5);
    expect(MilkProductionLog::query()->count())->toBeGreaterThanOrEqual(7);
    expect(NotificationDelivery::query()->count())->toBeGreaterThanOrEqual(2);
    expect(MemberAssignment::query()->count())->toBeGreaterThanOrEqual(3);
    expect(ApprovalEvent::query()->count())->toBeGreaterThanOrEqual(5);
});
