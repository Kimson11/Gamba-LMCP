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
        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            // Cooperative scope is mandatory for every member record.
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();
            // Cluster assignment may be deferred during onboarding.
            $table->foreignId('cluster_id')->nullable()->constrained('clusters')->nullOnDelete();
            // Optional link to a user login profile.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Cooperative-local identifier visible in admin and receipts.
            $table->string('member_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['cooperative_id', 'member_number'], 'members_cooperative_member_number_unique');
            $table->index(['cluster_id', 'status'], 'members_cluster_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
