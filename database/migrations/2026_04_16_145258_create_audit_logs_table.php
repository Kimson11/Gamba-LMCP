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
        // The audit_logs table is the tamper-evident event ledger for the system.
        //
        // Rules (enforced by the AuditLogger service):
        //  1. Rows are NEVER updated or deleted — only inserted.
        //  2. Every API write that changes state must produce at least one audit row.
        //  3. The correlation_id ties one user action to multiple internal events.
        //
        // Example row:
        //   action: 'milk_log.created'
        //   actor_id: 42, actor_role: 'member'
        //   subject_type: 'App\Models\MilkLog', subject_id: 101
        //   cooperative_id: 7
        //   correlation_id: 'uuid-of-the-originating-request'
        //   context: { quantity_litres: 12.5, cluster_id: 3 }
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();

            // ── Who acted ─────────────────────────────────────────────────
            // Nullable to support system-generated audit rows (e.g. scheduled jobs).
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // We snapshot the role at write time so historical audits reflect
            // what the actor's role was, even if the role changes later.
            $table->string('actor_role')->nullable(); // e.g. 'member', 'coop_admin'

            // ── What happened ─────────────────────────────────────────────
            // Dot-notation event name: domain.verb (e.g. 'payout.approved')
            $table->string('action');  // e.g. 'milk_log.created'

            // The model/entity that was affected (polymorphic reference).
            $table->string('subject_type')->nullable();  // e.g. 'App\Models\MilkLog'
            $table->unsignedBigInteger('subject_id')->nullable();  // e.g. 101

            // ── Where it happened ─────────────────────────────────────────
            // Cooperative context — null for system-level events.
            $table->unsignedBigInteger('cooperative_id')->nullable();

            // ── Tracing ───────────────────────────────────────────────────
            // Ties this audit row back to the originating HTTP request.
            // Multiple audit rows from one request share the same correlation_id.
            $table->uuid('correlation_id')->nullable();

            // ── Extra detail ──────────────────────────────────────────────
            // Free-form key-value context; avoids separate columns for each domain.
            // Example: { 'quantity_litres': 12.5, 'cluster_id': 3 }
            $table->json('context')->nullable();

            // Only created_at is meaningful; we never update audit rows.
            $table->timestamp('created_at')->useCurrent();

            // ── Indexes ───────────────────────────────────────────────────
            // These index patterns match the most common audit query patterns:
            //   "show all actions by user X"
            $table->index('actor_id');
            //   "show all events for a given request trace"
            $table->index('correlation_id');
            //   "show audit trail for a specific entity"
            $table->index(['subject_type', 'subject_id']);
            //   "show events within a cooperative"
            $table->index('cooperative_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
