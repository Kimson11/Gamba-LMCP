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
        Schema::table('member_assignments', function (Blueprint $table): void {
            $table->string('approval_status')->default('pending_approval')->after('reason');
            $table->foreignId('approved_by_user_id')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->foreignId('rejected_by_user_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by_user_id');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            $table->index(['cooperative_id', 'approval_status'], 'member_assignments_coop_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_assignments', function (Blueprint $table): void {
            $table->dropIndex('member_assignments_coop_status_idx');
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropConstrainedForeignId('rejected_by_user_id');
            $table->dropColumn(['approval_status', 'approved_at', 'rejected_at', 'rejection_reason']);
        });
    }
};
