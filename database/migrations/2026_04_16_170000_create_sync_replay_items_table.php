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
        // Sync replay ledger table.
        //
        // This table stores per-item reconciliation outcomes from /sync/batch.
        // It serves two key purposes:
        //  1) Duplicate detection using (cooperative_scope_id, client_request_id, request_type)
        //  2) Operational history for /sync/status diagnostics
        //
        // Example row:
        //   client_request_id: 5f2716c0-1fdc-4ef2-a9fd-92a36dc67a14
        //   request_type: milk_log_create
        //   offline_class: A
        //   status: synced
        //   entity_type: milk_log
        //   entity_id: 1001
        Schema::create('sync_replay_items', function (Blueprint $table): void {
            $table->id();

            // Actor who submitted this replay item.
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();

            // Scope boundary used by duplicate detection rules.
            // 0 means cooperative scope is unknown/unassigned for this actor.
            $table->unsignedBigInteger('cooperative_scope_id')->default(0);

            // Core request identity fields from the sync contract.
            $table->uuid('client_request_id');
            $table->string('request_type');
            $table->string('offline_class', 1); // A | B | C

            // Hash of normalized payload used for duplicate mismatch detection.
            $table->string('payload_hash', 64);

            // Reconciliation outcome status.
            // Allowed values: synced, duplicate, rejected, conflict, blocked, failed_sync
            $table->string('status');

            // Optional domain entity reference created/affected by this item.
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('server_state')->nullable();

            // Conflict-specific diagnostics.
            $table->string('conflict_code')->nullable();
            $table->json('server_state_summary')->nullable();
            $table->boolean('resolution_required')->default(false);
            $table->json('resolution_options')->nullable();

            // Human-readable error details for rejected/blocked/failed outcomes.
            $table->text('error_message')->nullable();

            // Item processing timestamp from the server.
            $table->timestamp('processed_at')->useCurrent();

            $table->timestamps();

            // Mandatory duplicate key as specified by the offline spec.
            $table->unique(
                ['cooperative_scope_id', 'client_request_id', 'request_type'],
                'sync_replay_items_unique_request'
            );

            // Common query patterns for /sync/status and diagnostics.
            $table->index(['actor_id', 'status'], 'sync_replay_items_actor_status_idx');
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_replay_items');
    }
};
