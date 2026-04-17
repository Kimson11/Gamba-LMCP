<?php

use App\Models\AuditLog;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\SyncConflictAction;
use App\Models\SyncReplayItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Build a fully valid replay item payload for sync batch tests.
 *
 * We keep this helper local to this file so each test remains easy to read:
 * the test can focus on the behavior being asserted and only override the
 * fields relevant to that scenario.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function makeConflictSyncItem(array $overrides = []): array
{
    return array_merge([
        'client_request_id' => (string) str()->uuid(),
        'request_type' => 'milk_log_create',
        'offline_class' => 'A',
        'occurred_at' => now()->subMinute()->toIso8601String(),
        'payload' => [
            'member_id' => 404,
            'quantity_liters' => 9.5,
            'production_date' => now()->toDateString(),
        ],
    ], $overrides);
}

it('lists unresolved conflicts for the authenticated actor', function (): void {
    $member = User::factory()->member()->create();

    // Seed one conflict row using the public sync API so test data mirrors
    // real production write paths and all processor guards are exercised.
    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-conflict-list',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'payload' => [
                    'server_status' => 'approved',
                ],
            ])],
        ],
    ])->assertSuccessful();

    $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/sync/conflicts');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.pagination.total', 1)
        ->assertJsonPath('data.items.0.conflict_code', 'approval_state_locked')
        ->assertJsonPath('data.items.0.resolution_required', true);
});

it('returns a single conflict detail by id', function (): void {
    $member = User::factory()->member()->create();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-conflict-detail',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'payload' => [
                    'scope_changed' => true,
                ],
            ])],
        ],
    ])->assertSuccessful();

    $conflict = SyncReplayItem::query()->firstOrFail();

    $response = $this->actingAs($member, 'sanctum')->getJson("/api/v1/sync/conflicts/{$conflict->id}");

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.id', $conflict->id)
        ->assertJsonPath('data.conflict_code', 'scope_changed')
        ->assertJsonPath('data.resolution_required', true);
});

it('prevents non-privileged users from resolving protected conflicts', function (): void {
    $member = User::factory()->member()->create();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-conflict-permission',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'payload' => [
                    'server_status' => 'approved',
                ],
            ])],
        ],
    ])->assertSuccessful();

    $conflict = SyncReplayItem::query()->firstOrFail();

    $response = $this->actingAs($member, 'sanctum')->postJson("/api/v1/sync/conflicts/{$conflict->id}/resolve", [
        'data' => [
            'action' => 'view_server_record',
        ],
    ]);

    $response
        ->assertForbidden()
        ->assertJsonPath('code', 'permission_denied');

    expect($conflict->fresh()->resolution_required)->toBeTrue();
});

it('allows privileged users to resolve protected conflicts with a reason', function (): void {
    $member = User::factory()->member()->create();
    $admin = User::factory()->coopAdmin()->create();

    // Member creates a protected conflict row.
    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-conflict-admin',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'payload' => [
                    'server_status' => 'approved',
                ],
            ])],
        ],
    ])->assertSuccessful();

    $conflict = SyncReplayItem::query()->firstOrFail();

    // Admin resolves it using view_server_record with explicit reason,
    // satisfying privileged-resolution governance rules.
    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/sync/conflicts/{$conflict->id}/resolve", [
        'data' => [
            'action' => 'view_server_record',
            'reason' => 'Server-approved record retained; member notified to resubmit corrected data.',
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.conflict.resolution_required', false)
        ->assertJsonPath('data.conflict.resolution_action', 'view_server_record')
        ->assertJsonPath('data.conflict.resolved_by_user_id', $admin->id);

    $resolved = $conflict->fresh();

    expect($resolved->resolution_required)->toBeFalse();
    expect($resolved->resolution_action)->toBe('view_server_record');
    expect($resolved->resolved_by_user_id)->toBe($admin->id);

    expect(AuditLog::query()->where('action', 'sync.conflict_resolved')->count())->toBe(1);
});

it('creates replacement submission during conflict resolution', function (): void {
    $member = User::factory()->member()->create();

    $cooperative = Cooperative::query()->create([
        'name' => 'Replacement Coop',
        'code' => 'RPL-01',
        'country_code' => 'NG',
        'status' => 'active',
    ]);

    $cluster = Cluster::query()->create([
        'cooperative_id' => $cooperative->id,
        'name' => 'Replacement Cluster',
        'status' => 'active',
    ]);

    $syncMember = Member::query()->create([
        'cooperative_id' => $cooperative->id,
        'cluster_id' => $cluster->id,
        'member_number' => 'SYNC-R-001',
        'first_name' => 'Asabe',
        'last_name' => 'Rabiu',
        'status' => 'active',
    ]);

    // Seed a conflict that does not require privileged role intervention.
    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-conflict-replace',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'payload' => [
                    'scope_changed' => true,
                ],
            ])],
        ],
    ])->assertSuccessful();

    $conflict = SyncReplayItem::query()->firstOrFail();

    $response = $this->actingAs($member, 'sanctum')->postJson("/api/v1/sync/conflicts/{$conflict->id}/resolve", [
        'data' => [
            'action' => 'create_replacement_submission',
            'replacement_item' => makeConflictSyncItem([
                'client_request_id' => (string) str()->uuid(),
                'payload' => [
                    'member_id' => $syncMember->id,
                    'quantity_liters' => 12.2,
                    'production_date' => now()->toDateString(),
                ],
            ]),
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.conflict.resolution_required', false)
        ->assertJsonPath('data.conflict.resolution_action', 'create_replacement_submission')
        ->assertJsonPath('data.replacement_result.status', 'synced');

    expect(SyncReplayItem::query()->count())->toBe(2);
});

it('records conflict resolution in append-only history timeline', function (): void {
    $member = User::factory()->member()->create();
    $admin = User::factory()->coopAdmin()->create();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-history-seed',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'payload' => [
                    'server_status' => 'approved',
                ],
            ])],
        ],
    ])->assertSuccessful();

    $conflict = SyncReplayItem::query()->firstOrFail();

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/sync/conflicts/{$conflict->id}/resolve", [
        'data' => [
            'action' => 'view_server_record',
            'reason' => 'Reviewed and confirmed server state as authoritative.',
        ],
    ])->assertSuccessful();

    $history = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/sync/conflicts/{$conflict->id}/history");

    $history
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.action_type', 'resolved')
        ->assertJsonPath('data.items.0.sync_replay_item_id', $conflict->id);

    expect(SyncConflictAction::query()->count())->toBe(1);
});

it('allows reviewers to add conflict notes and expose them in history', function (): void {
    $member = User::factory()->member()->create();
    $reviewer = User::factory()->coopAdmin()->create();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-note-seed',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'payload' => [
                    'scope_changed' => true,
                ],
            ])],
        ],
    ])->assertSuccessful();

    $conflict = SyncReplayItem::query()->firstOrFail();

    $noteResponse = $this->actingAs($reviewer, 'sanctum')->postJson("/api/v1/sync/conflicts/{$conflict->id}/notes", [
        'data' => [
            'note' => 'Reviewer requested updated cooperative assignment before replay.',
        ],
    ]);

    $noteResponse
        ->assertCreated()
        ->assertJsonPath('data.action_type', 'note_added');

    $history = $this->actingAs($reviewer, 'sanctum')->getJson("/api/v1/sync/conflicts/{$conflict->id}/history");

    $history
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.action_type', 'note_added')
        ->assertJsonPath('data.items.0.note', 'Reviewer requested updated cooperative assignment before replay.');
});

it('prevents non-reviewers from adding conflict notes', function (): void {
    $member = User::factory()->member()->create();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-note-forbidden-seed',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'payload' => [
                    'scope_changed' => true,
                ],
            ])],
        ],
    ])->assertSuccessful();

    $conflict = SyncReplayItem::query()->firstOrFail();

    $response = $this->actingAs($member, 'sanctum')->postJson("/api/v1/sync/conflicts/{$conflict->id}/notes", [
        'data' => [
            'note' => 'Member should not be able to add reviewer note.',
        ],
    ]);

    $response
        ->assertForbidden()
        ->assertJsonPath('code', 'permission_denied');
});
