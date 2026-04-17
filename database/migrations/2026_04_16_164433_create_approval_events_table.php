<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Append-only event stream for all approval-governed entities.
        //
        // Example lifecycle for entity_type='payout_request', entity_id=9001:
        //   1) submitted  -> current_status='pending_approval'
        //   2) approved   -> current_status='approved'
        //   3) reversed   -> current_status='reversed'
        //
        // We never update previous rows. Each transition is an immutable event.
        Schema::create('approval_events', function (Blueprint $table): void {
            $table->id();

            // The business entity under approval (polymorphic reference by type+id).
            $table->string('entity_type'); // Example: 'payout_request', 'feed_distribution'
            $table->unsignedBigInteger('entity_id');

            // Event and resulting status after this event.
            $table->string('event_type'); // submitted | approved | rejected | reversed
            $table->string('current_status'); // pending_approval | approved | rejected | reversed

            // Who performed this transition.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role')->nullable();

            // Human explanation (required for reject/reverse).
            $table->text('reason')->nullable();

            // Flexible domain-specific metadata.
            // Example: { "amount": 5000, "currency": "NGN", "source": "mobile" }
            $table->json('metadata')->nullable();

            // Correlation ID traces all events created by one request.
            $table->uuid('correlation_id')->nullable();

            // Event stream is append-only; created_at is the event timestamp.
            $table->timestamp('created_at')->useCurrent();

            // Query optimization:
            // 1) Fast read of one entity's event timeline.
            $table->index(['entity_type', 'entity_id', 'id'], 'approval_events_entity_timeline_idx');
            // 2) Filter by current status.
            $table->index('current_status');
            // 3) Trace all events from one request.
            $table->index('correlation_id');
            // 4) Actor-centric audit views.
            $table->index('actor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_events');
    }
};
