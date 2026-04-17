<?php

use App\Models\AuditLog;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sends a database notification and marks delivery as sent', function () {
    $recipient = User::factory()->member()->create();
    $actor = User::factory()->coopAdmin()->create();

    $delivery = app(NotificationDispatcher::class)->sendDatabase(
        recipient: $recipient,
        type: 'approval.pending',
        title: 'Approval Needed',
        message: 'Payout #9001 needs review.',
        payload: ['entity_type' => 'payout_request', 'entity_id' => 9001],
        actor: $actor,
    );

    expect($delivery->status)->toBe('sent')
        ->and($delivery->channel)->toBe('database')
        ->and($delivery->notification_type)->toBe('approval.pending')
        ->and($delivery->delivered_at)->not->toBeNull();

    // The built-in notifications table should contain one row for this recipient.
    expect($recipient->notifications()->count())->toBe(1);
});

it('writes a companion audit event when notification is sent', function () {
    $recipient = User::factory()->member()->create();
    $actor = User::factory()->coopAdmin()->create();

    app(NotificationDispatcher::class)->sendDatabase(
        recipient: $recipient,
        type: 'approval.pending',
        title: 'Approval Needed',
        message: 'Please review payout #9010.',
        payload: ['entity_type' => 'payout_request', 'entity_id' => 9010],
        actor: $actor,
    );

    $audit = AuditLog::query()->where('action', 'notification.sent')->latest('id')->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($actor->id)
        ->and($audit->context['recipient_user_id'])->toBe($recipient->id);
});

it('stores a delivery ledger row for each notification send attempt', function () {
    $recipient = User::factory()->member()->create();

    app(NotificationDispatcher::class)->sendDatabase(
        recipient: $recipient,
        type: 'sync.failed',
        title: 'Sync Failed',
        message: 'Last sync failed. Tap to retry.',
        payload: ['queue_size' => 3],
    );

    expect(NotificationDelivery::count())->toBe(1);

    $ledgerRow = NotificationDelivery::first();

    expect($ledgerRow->title)->toBe('Sync Failed')
        ->and($ledgerRow->message)->toBe('Last sync failed. Tap to retry.')
        ->and($ledgerRow->payload['queue_size'])->toBe(3);
});
