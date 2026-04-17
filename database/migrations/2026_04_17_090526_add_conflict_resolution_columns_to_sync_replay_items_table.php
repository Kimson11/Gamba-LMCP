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
        Schema::table('sync_replay_items', function (Blueprint $table): void {
            // Resolution metadata columns allow the system to track who resolved
            // a conflict, what action was taken, and when it was resolved.
            //
            // Example:
            //   resolution_action = 'create_replacement_submission'
            //   resolution_reason = 'Server record approved by admin; resubmitted corrected payload.'
            //   resolved_by_user_id = 77
            //   resolved_at = 2026-04-17T09:10:00Z
            $table->string('resolution_action')->nullable()->after('resolution_options');
            $table->text('resolution_reason')->nullable()->after('resolution_action');
            $table->foreignId('resolved_by_user_id')->nullable()->after('resolution_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('resolved_by_user_id');

            $table->index(['status', 'resolution_required'], 'sync_replay_items_resolution_queue_idx');
            $table->index('resolved_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_replay_items', function (Blueprint $table): void {
            $table->dropIndex('sync_replay_items_resolution_queue_idx');
            $table->dropIndex(['resolved_by_user_id']);
            $table->dropConstrainedForeignId('resolved_by_user_id');
            $table->dropColumn(['resolution_action', 'resolution_reason', 'resolved_at']);
        });
    }
};
