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
        ->assertJsonPath('data.queue_meta.sort_by', 'priority')
        ->assertJsonPath('data.queue_meta.ordering_policy', 'priority_then_oldest')
        ->assertJsonPath('data.queue_meta.priority_counts.1', 1)
        ->assertJsonPath('data.queue_meta.priority_counts.2', 0)
        ->assertJsonPath('data.queue_meta.priority_counts.3', 0)
        ->assertJsonPath('data.queue_meta.recommended_next_actions.priority_1', 'review_with_privileged_reviewer')
        ->assertJsonPath('data.queue_meta.recommended_next_actions.priority_2', 'none')
        ->assertJsonPath('data.queue_meta.recommended_next_actions.priority_3', 'none')
        ->assertJsonPath('data.queue_meta.recommended_next_actions.sla_breach', 'none')
        ->assertJsonPath('data.queue_meta.reviewer_capacity_hint', 'elevated')
        ->assertJsonPath('data.queue_meta.total_unresolved_conflicts', 1)
        ->assertJsonPath('data.items.0.conflict_code', 'approval_state_locked')
        ->assertJsonPath('data.items.0.resolution_required', true);

    expect($response->json('data.queue_meta.oldest_conflict_age_seconds'))->toBeInt();
    expect($response->json('data.queue_meta.average_conflict_age_seconds'))->toBeInt();
    expect($response->json('data.queue_meta.sla_buckets'))->toBeArray();
});

it('orders unresolved conflicts by priority then age and exposes age metrics', function (): void {
    $member = User::factory()->member()->create();

    $scopeChangedNewerRequestId = (string) str()->uuid();
    $scopeChangedOlderRequestId = (string) str()->uuid();
    $protectedRequestId = (string) str()->uuid();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-priority-order-1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'client_request_id' => $scopeChangedNewerRequestId,
                'payload' => [
                    'scope_changed' => true,
                ],
            ])],
        ],
    ])->assertSuccessful();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-priority-order-2',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'client_request_id' => $scopeChangedOlderRequestId,
                'payload' => [
                    'scope_changed' => true,
                ],
            ])],
        ],
    ])->assertSuccessful();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-priority-order-3',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'client_request_id' => $protectedRequestId,
                'payload' => [
                    'server_status' => 'approved',
                ],
            ])],
        ],
    ])->assertSuccessful();

    $scopeChangedNewer = SyncReplayItem::query()->where('client_request_id', $scopeChangedNewerRequestId)->firstOrFail();
    $scopeChangedOlder = SyncReplayItem::query()->where('client_request_id', $scopeChangedOlderRequestId)->firstOrFail();
    $protected = SyncReplayItem::query()->where('client_request_id', $protectedRequestId)->firstOrFail();

    $scopeChangedNewer->update(['processed_at' => now()->subHour()]);
    $scopeChangedOlder->update(['processed_at' => now()->subHours(5)]);
    $protected->update(['processed_at' => now()->subMinutes(10)]);

    $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/sync/conflicts');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.id', $protected->id)
        ->assertJsonPath('data.items.0.priority_rank', 1)
        ->assertJsonPath('data.items.0.priority_label', 'high')
        ->assertJsonPath('data.items.1.id', $scopeChangedOlder->id)
        ->assertJsonPath('data.items.2.id', $scopeChangedNewer->id);

    $items = $response->json('data.items');

    expect($items)->toBeArray()->toHaveCount(3);
    expect($items[0]['conflict_age_seconds'])->toBeInt()->toBeGreaterThanOrEqual(0);
    expect($items[1]['conflict_age_seconds'])->toBeInt()->toBeGreaterThanOrEqual(0);
    expect($items[2]['conflict_age_seconds'])->toBeInt()->toBeGreaterThanOrEqual(0);
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
    ], [
        'Idempotency-Key' => 'sync-conflict-resolve-forbidden',
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
    ], [
        'Idempotency-Key' => 'sync-conflict-resolve-admin',
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
    ], [
        'Idempotency-Key' => 'sync-conflict-resolve-replacement',
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
    ], [
        'Idempotency-Key' => 'sync-conflict-resolve-history',
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
    ], [
        'Idempotency-Key' => 'sync-conflict-note-reviewer',
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
    ], [
        'Idempotency-Key' => 'sync-conflict-note-forbidden',
    ]);

    $response
        ->assertForbidden()
        ->assertJsonPath('code', 'permission_denied');
});

it('excludes resolved conflicts from conflict breakdown diagnostics', function (): void {
    $member = User::factory()->member()->create();
    $admin = User::factory()->coopAdmin()->create();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-breakdown-seed',
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
            'reason' => 'Server truth retained after governance review.',
        ],
    ], [
        'Idempotency-Key' => 'sync-conflict-resolve-breakdown',
    ])->assertSuccessful();

    $status = $this->actingAs($member, 'sanctum')->getJson('/api/v1/sync/status');

    $status
        ->assertSuccessful()
        ->assertJsonPath('data.counts.conflict', 1)
        ->assertJsonPath('data.resolution_queue_count', 0)
        ->assertJsonPath('data.conflict_breakdown.approval_state_locked', null);
});

it('supports conflict queue filtering and alternate sorting options', function (): void {
    $member = User::factory()->member()->create();

    $scopeRequestId = (string) str()->uuid();
    $dependencyRequestId = (string) str()->uuid();
    $protectedRequestId = (string) str()->uuid();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-filter-sort-1',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'client_request_id' => $scopeRequestId,
                'payload' => [
                    'scope_changed' => true,
                ],
            ])],
        ],
    ])->assertSuccessful();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-filter-sort-2',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'client_request_id' => $dependencyRequestId,
                'payload' => [
                    'depends_on_client_request_id' => 'missing-dependency-id',
                    'depends_on_request_type' => 'milk_log_create',
                ],
            ])],
        ],
    ])->assertSuccessful();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-filter-sort-3',
            'sent_at' => now()->toIso8601String(),
            'items' => [makeConflictSyncItem([
                'client_request_id' => $protectedRequestId,
                'payload' => [
                    'server_status' => 'approved',
                ],
            ])],
        ],
    ])->assertSuccessful();

    $scopeConflict = SyncReplayItem::query()->where('client_request_id', $scopeRequestId)->firstOrFail();
    $dependencyConflict = SyncReplayItem::query()->where('client_request_id', $dependencyRequestId)->firstOrFail();
    $protectedConflict = SyncReplayItem::query()->where('client_request_id', $protectedRequestId)->firstOrFail();

    $scopeConflict->update(['processed_at' => now()->subHours(5)]);
    $dependencyConflict->update(['processed_at' => now()->subHours(2)]);
    $protectedConflict->update(['processed_at' => now()->subMinutes(30)]);

    $priorityFiltered = $this->actingAs($member, 'sanctum')->getJson('/api/v1/sync/conflicts?priority_rank=1');

    $priorityFiltered
        ->assertSuccessful()
        ->assertJsonPath('data.pagination.total', 1)
        ->assertJsonPath('data.queue_meta.applied_filters.priority_rank', 1)
        ->assertJsonPath('data.queue_meta.priority_counts.1', 1)
        ->assertJsonPath('data.queue_meta.priority_counts.2', 0)
        ->assertJsonPath('data.queue_meta.priority_counts.3', 0)
        ->assertJsonPath('data.queue_meta.reviewer_capacity_hint', 'elevated')
        ->assertJsonPath('data.queue_meta.total_unresolved_conflicts', 1)
        ->assertJsonPath('data.items.0.id', $protectedConflict->id)
        ->assertJsonPath('data.items.0.priority_rank', 1);

    expect($priorityFiltered->json('data.queue_meta.sla_buckets'))->toBe([
        '<1h' => 1,
        '1-4h' => 0,
        '4-24h' => 0,
        '>24h' => 0,
    ]);

    $codeFiltered = $this->actingAs($member, 'sanctum')->getJson('/api/v1/sync/conflicts?conflict_code=dependency_failed');

    $codeFiltered
        ->assertSuccessful()
        ->assertJsonPath('data.pagination.total', 1)
        ->assertJsonPath('data.queue_meta.applied_filters.conflict_code', 'dependency_failed')
        ->assertJsonPath('data.queue_meta.priority_counts.1', 0)
        ->assertJsonPath('data.queue_meta.priority_counts.2', 1)
        ->assertJsonPath('data.queue_meta.priority_counts.3', 0)
        ->assertJsonPath('data.queue_meta.reviewer_capacity_hint', 'normal')
        ->assertJsonPath('data.queue_meta.total_unresolved_conflicts', 1)
        ->assertJsonPath('data.items.0.id', $dependencyConflict->id);

    expect($codeFiltered->json('data.queue_meta.sla_buckets'))->toBe([
        '<1h' => 0,
        '1-4h' => 1,
        '4-24h' => 0,
        '>24h' => 0,
    ]);

    $ageFiltered = $this->actingAs($member, 'sanctum')->getJson('/api/v1/sync/conflicts?min_age_hours=2');

    $ageFiltered
        ->assertSuccessful()
        ->assertJsonPath('data.pagination.total', 2)
        ->assertJsonPath('data.queue_meta.priority_counts.1', 0)
        ->assertJsonPath('data.queue_meta.priority_counts.2', 1)
        ->assertJsonPath('data.queue_meta.priority_counts.3', 1)
        ->assertJsonPath('data.queue_meta.reviewer_capacity_hint', 'normal')
        ->assertJsonPath('data.queue_meta.total_unresolved_conflicts', 2)
        ->assertJsonPath('data.queue_meta.applied_filters.min_age_hours', 2);

    expect($ageFiltered->json('data.queue_meta.sla_buckets'))->toBe([
        '<1h' => 0,
        '1-4h' => 1,
        '4-24h' => 1,
        '>24h' => 0,
    ]);

    $newestFirst = $this->actingAs($member, 'sanctum')->getJson('/api/v1/sync/conflicts?sort_by=newest');

    $newestFirst
        ->assertSuccessful()
        ->assertJsonPath('data.queue_meta.sort_by', 'newest')
        ->assertJsonPath('data.queue_meta.ordering_policy', 'newest')
        ->assertJsonPath('data.queue_meta.priority_counts.1', 1)
        ->assertJsonPath('data.queue_meta.priority_counts.2', 1)
        ->assertJsonPath('data.queue_meta.priority_counts.3', 1)
        ->assertJsonPath('data.queue_meta.reviewer_capacity_hint', 'elevated')
        ->assertJsonPath('data.queue_meta.total_unresolved_conflicts', 3)
        ->assertJsonPath('data.items.0.id', $protectedConflict->id)
        ->assertJsonPath('data.items.1.id', $dependencyConflict->id)
        ->assertJsonPath('data.items.2.id', $scopeConflict->id);

    expect($newestFirst->json('data.queue_meta.oldest_conflict_age_seconds'))->toBeInt()->toBeGreaterThanOrEqual(0);
    expect($newestFirst->json('data.queue_meta.average_conflict_age_seconds'))->toBeInt()->toBeGreaterThanOrEqual(0);
    expect($newestFirst->json('data.queue_meta.recommended_next_actions'))->toBe([
        'priority_1' => 'review_with_privileged_reviewer',
        'priority_2' => 'resolve_dependency_chain',
        'priority_3' => 'review_standard_conflict_queue',
        'sla_breach' => 'none',
    ]);
    expect($newestFirst->json('data.queue_meta.sla_buckets'))->toBe([
        '<1h' => 1,
        '1-4h' => 1,
        '4-24h' => 1,
        '>24h' => 0,
    ]);
});

it('allows capacity thresholds to be tuned through sync configuration', function (): void {
    config()->set('sync.conflicts.capacity.elevated_high_priority', 2);

    $member = User::factory()->member()->create();

    $this->actingAs($member, 'sanctum')->postJson('/api/v1/sync/batch', [
        'data' => [
            'device_id' => 'android-capacity-config-seed',
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
        ->assertJsonPath('data.queue_meta.reviewer_capacity_hint', 'normal')
        ->assertJsonPath('data.queue_meta.priority_counts.1', 1);
});
