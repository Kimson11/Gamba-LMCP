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
        Schema::create('sync_conflict_actions', function (Blueprint $table): void {
            $table->id();

            // Parent conflict row this action belongs to.
            $table->foreignId('sync_replay_item_id')->constrained('sync_replay_items')->cascadeOnDelete();

            // Actor snapshot for immutable governance trail.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role')->nullable();

            // Canonical action type: resolved | note_added.
            $table->string('action_type');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('correlation_id')->nullable();

            // Append-only stream: created_at only, no updated_at.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sync_replay_item_id', 'id'], 'sync_conflict_actions_timeline_idx');
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_conflict_actions');
    }
};
