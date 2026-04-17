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
        Schema::create('member_assignments', function (Blueprint $table): void {
            $table->id();

            // Assignment event subject.
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            // Cooperative scope for query filtering and governance reporting.
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();

            // Source and destination cluster references.
            $table->foreignId('from_cluster_id')->nullable()->constrained('clusters')->nullOnDelete();
            $table->foreignId('to_cluster_id')->constrained('clusters')->cascadeOnDelete();

            // Actor who executed the assignment decision.
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Optional reviewer/operator note describing why reassignment happened.
            $table->text('reason')->nullable();
            $table->uuid('correlation_id')->nullable();

            // Append-only timeline: created_at only.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['member_id', 'id'], 'member_assignments_member_timeline_idx');
            $table->index(['cooperative_id', 'created_at'], 'member_assignments_cooperative_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_assignments');
    }
};
