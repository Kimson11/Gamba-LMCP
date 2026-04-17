<?php

use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MilkProductionLog;
use App\Models\SyncReplayItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Build a valid sync item payload with sensible defaults.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function makeSyncItem(array $overrides = []): array
{
    return array_merge([
        'client_request_id' => (string) str()->uuid(),
        'request_type' => 'milk_log_create',
        'offline_class' => 'A',
        'occurred_at' => now()->subMinute()->toIso8601String(),
        'payload' => [
            'member_id' => 101,
            'quantity_liters' => 8.5,
            'production_date' => now()->toDateString(),
        ],
    ], $overrides);
}

/**
 * Seed one cooperative/cluster/member chain for milk_log_create replay tests.
 */
function createSyncMember(): Member
{
    $cooperative = Cooperative::query()->create([
        'name' => 'Sync Milk Coop',
        'code' => 'SMC-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Sync Cluster A',
        'status' => 'active',
    ]);

    return Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'SYNC-M-001',
        'first_name' => 'Rahma',
        'last_name' => 'Sule',
        'status' => 'active',
    ]);
}

it('processes class-A replayable items as synced', function () {
    $user = User::factory()->member()->create();
    $member = createSyncMember();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'payload' => [
                    'member_id' => $member->id,
                    'quantity_liters' => 8.5,
                    'production_date' => now()->toDateString(),
                ],
            ])],
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.processed', 1)
        ->assertJsonPath('data.failed', 0)
        ->assertJsonPath('data.results.0.status', 'synced')
        ->assertJsonPath('data.results.0.entity_type', 'milk_log');

    expect(SyncReplayItem::count())->toBe(1);
    expect(MilkProductionLog::count())->toBe(1);
});

it('returns duplicate for same request key and same payload', function () {
    $user = User::factory()->member()->create();
    $member = createSyncMember();
    $clientRequestId = (string) str()->uuid();

    $item = makeSyncItem([
        'client_request_id' => $clientRequestId,
        'payload' => [
            'member_id' => $member->id,
            'quantity_liters' => 8.5,
            'production_date' => now()->toDateString(),
        ],
    ]);

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [$item],
        ],
    ])->assertSuccessful();

    $duplicate = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [$item],
        ],
    ]);

    $duplicate
        ->assertSuccessful()
        ->assertJsonPath('data.results.0.status', 'duplicate');

    expect(MilkProductionLog::count())->toBe(1);
});

it('returns conflict for same request key but different payload', function () {
    $user = User::factory()->member()->create();
    $clientRequestId = (string) str()->uuid();

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'client_request_id' => $clientRequestId,
                'payload' => ['member_id' => 101, 'quantity_liters' => 8.5],
            ])],
        ],
    ])->assertSuccessful();

    $conflict = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'client_request_id' => $clientRequestId,
                'payload' => ['member_id' => 101, 'quantity_liters' => 9.9],
            ])],
        ],
    ]);

    $conflict
        ->assertSuccessful()
        ->assertJsonPath('data.results.0.status', 'conflict')
        ->assertJsonPath('data.results.0.conflict_code', 'duplicate_with_payload_mismatch');
});

it('blocks class-C online-only commit items during replay', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'request_type' => 'approval_decision',
                'offline_class' => 'C',
            ])],
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.results.0.status', 'blocked');
});

it('returns sync status summary and recent results', function () {
    $user = User::factory()->member()->create();
    $member = createSyncMember();

    // Seed one synced item via API so status endpoint has data.
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [
                // One synced item contributes to terminal_success.
                makeSyncItem([
                    'payload' => [
                        'member_id' => $member->id,
                        'quantity_liters' => 8.5,
                        'production_date' => now()->toDateString(),
                    ],
                ]),
                // One blocked item contributes to requires_user_or_admin_action.
                makeSyncItem([
                    'request_type' => 'approval_decision',
                    'offline_class' => 'C',
                ]),
            ],
        ],
    ])->assertSuccessful();

    $status = $this->actingAs($user, 'sanctum')->getJson('/api/v1/sync/status');

    $status
        ->assertSuccessful()
        ->assertJsonPath('data.counts.synced', 1)
        ->assertJsonPath('data.counts.blocked', 1)
        ->assertJsonPath('data.retry_eligibility.retryable_now', 0)
        ->assertJsonPath('data.retry_eligibility.requires_user_or_admin_action', 1)
        ->assertJsonPath('data.retry_eligibility.terminal_success', 1)
        ->assertJsonPath('data.resolution_queue_count', 0)
        ->assertJsonStructure([
            'data' => [
                'counts',
                'retry_eligibility',
                'resolution_queue_count',
                'conflict_breakdown',
                'failure_reason_summary',
                'recent_results',
            ],
        ]);
});

it('rejects milk_log_create replay when member cluster does not match payload cluster', function () {
    $user = User::factory()->member()->create();
    $member = createSyncMember();

    $otherCluster = Cluster::query()->create([
        'cooperative_id' => $member->cooperative_id,
        'name' => 'Wrong Sync Cluster',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'payload' => [
                    'member_id' => $member->id,
                    'cluster_id' => $otherCluster->id,
                    'quantity_liters' => 7.8,
                    'production_date' => now()->toDateString(),
                ],
            ])],
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.results.0.status', 'rejected');

    expect(MilkProductionLog::count())->toBe(0);
});

it('includes conflict and failure diagnostics in sync status output', function () {
    $user = User::factory()->member()->create();

    // 1) Create a conflict item (approval_state_locked) with resolution_required=true.
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'payload' => [
                    'server_status' => 'approved',
                ],
            ])],
        ],
    ])->assertSuccessful();

    // 2) Create a rejected item with known error text.
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'request_type' => 'approval_decision',
                'offline_class' => 'A', // mismatched on purpose -> rejected
            ])],
        ],
    ])->assertSuccessful();

    $status = $this->actingAs($user, 'sanctum')->getJson('/api/v1/sync/status');

    $status
        ->assertSuccessful()
        ->assertJsonPath('data.counts.conflict', 1)
        ->assertJsonPath('data.counts.rejected', 1)
        ->assertJsonPath('data.resolution_queue_count', 1)
        ->assertJsonPath('data.conflict_breakdown.approval_state_locked', 1)
        ->assertJsonPath('data.retry_eligibility.requires_user_or_admin_action', 2);

    // Ensure failure_reason_summary is populated with at least one row.
    expect($status->json('data.failure_reason_summary'))->toBeArray()->not->toBeEmpty();
});

it('returns approval_state_locked when server status is approved', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'payload' => [
                    'server_status' => 'approved',
                    'member_id' => 101,
                ],
            ])],
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.results.0.status', 'conflict')
        ->assertJsonPath('data.results.0.conflict_code', 'approval_state_locked');
});

it('returns ledger_state_locked when server status is posted', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'payload' => [
                    'server_status' => 'posted',
                    'member_id' => 101,
                ],
            ])],
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.results.0.status', 'conflict')
        ->assertJsonPath('data.results.0.conflict_code', 'ledger_state_locked');
});

it('returns finalization_state_locked when server status is finalized', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'payload' => [
                    'server_status' => 'finalized',
                    'member_id' => 101,
                ],
            ])],
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.results.0.status', 'conflict')
        ->assertJsonPath('data.results.0.conflict_code', 'finalization_state_locked');
});

it('returns dependency_failed when prerequisite replay item is missing', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'payload' => [
                    'depends_on_client_request_id' => (string) str()->uuid(),
                    'depends_on_request_type' => 'milk_log_create',
                ],
            ])],
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.results.0.status', 'conflict')
        ->assertJsonPath('data.results.0.conflict_code', 'dependency_failed');
});

it('returns scope_changed conflict when payload indicates scope drift', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-2af1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeSyncItem([
                'payload' => [
                    'scope_changed' => true,
                ],
            ])],
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.results.0.status', 'conflict')
        ->assertJsonPath('data.results.0.conflict_code', 'scope_changed');
});
