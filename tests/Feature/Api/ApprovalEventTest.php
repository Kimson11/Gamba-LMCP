<?php

use App\Models\ApprovalEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an authenticated user to submit an entity for approval', function () {
    $member = User::factory()->member()->create();

    $response = $this->actingAs($member, 'sanctum')->postJson(
        '/api/v1/approvals/payout_request/9001/submit',
        ['metadata' => ['amount' => 5000]],
        ['Idempotency-Key' => 'approval-submit-9001']
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.event_type', 'submitted')
        ->assertJsonPath('data.current_status', 'pending_approval');

    expect(ApprovalEvent::count())->toBe(1);
});

it('denies non-privileged users from approve transition', function () {
    $member = User::factory()->member()->create();

    // First, member submits successfully (submit is open to authenticated users).
    $this->actingAs($member, 'sanctum')->postJson(
        '/api/v1/approvals/payout_request/9002/submit',
        [],
        ['Idempotency-Key' => 'approval-submit-9002']
    )->assertCreated();

    // Then, the same member tries to approve and should be denied by role middleware.
    $this->actingAs($member, 'sanctum')->postJson(
        '/api/v1/approvals/payout_request/9002/approve',
        [],
        ['Idempotency-Key' => 'approval-approve-9002']
    )
        ->assertForbidden()
        ->assertJsonPath('code', 'permission_denied');
});

it('allows privileged roles to approve after submit', function () {
    $member = User::factory()->member()->create();
    $coopAdmin = User::factory()->coopAdmin()->create();

    $this->actingAs($member, 'sanctum')->postJson(
        '/api/v1/approvals/payout_request/9003/submit',
        ['metadata' => ['amount' => 7000]],
        ['Idempotency-Key' => 'approval-submit-9003']
    )->assertCreated();

    $approveResponse = $this->actingAs($coopAdmin, 'sanctum')->postJson(
        '/api/v1/approvals/payout_request/9003/approve',
        ['metadata' => ['approved_by' => 'finance-board']],
        ['Idempotency-Key' => 'approval-approve-9003']
    );

    $approveResponse
        ->assertCreated()
        ->assertJsonPath('data.event_type', 'approved')
        ->assertJsonPath('data.current_status', 'approved');

    // Timeline should now have two immutable events: submitted -> approved.
    expect(ApprovalEvent::where('entity_type', 'payout_request')->where('entity_id', 9003)->count())->toBe(2);
});

it('returns transition error when approving without pending status', function () {
    $admin = User::factory()->coopAdmin()->create();

    // No submit event exists for entity 9004, so approve must fail.
    $response = $this->actingAs($admin, 'sanctum')->postJson(
        '/api/v1/approvals/payout_request/9004/approve',
        [],
        ['Idempotency-Key' => 'approval-approve-9004']
    );

    $response
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_approval_transition');
});

it('requires reason when rejecting or reversing', function () {
    $member = User::factory()->member()->create();
    $admin = User::factory()->coopAdmin()->create();

    $this->actingAs($member, 'sanctum')->postJson(
        '/api/v1/approvals/payout_request/9005/submit',
        [],
        ['Idempotency-Key' => 'approval-submit-9005']
    )->assertCreated();

    // Missing reason should trigger request validation before service execution.
    $this->actingAs($admin, 'sanctum')->postJson(
        '/api/v1/approvals/payout_request/9005/reject',
        [],
        ['Idempotency-Key' => 'approval-reject-9005']
    )
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed');
});

it('returns ordered timeline for one entity', function () {
    $member = User::factory()->member()->create();
    $admin = User::factory()->coopAdmin()->create();

    $this->actingAs($member, 'sanctum')->postJson(
        '/api/v1/approvals/payout_request/9006/submit',
        [],
        ['Idempotency-Key' => 'approval-submit-9006']
    )->assertCreated();

    $this->actingAs($admin, 'sanctum')->postJson(
        '/api/v1/approvals/payout_request/9006/approve',
        [],
        ['Idempotency-Key' => 'approval-approve-9006']
    )->assertCreated();

    $timeline = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/approvals/payout_request/9006/events');

    $timeline
        ->assertSuccessful()
        ->assertJsonPath('data.events.0.event_type', 'submitted')
        ->assertJsonPath('data.events.1.event_type', 'approved');
});
