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
        Schema::create('clusters', function (Blueprint $table): void {
            $table->id();
            // Each cluster is owned by exactly one cooperative.
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->string('name');
            // Optional cluster code for reporting alignment.
            $table->string('code')->nullable();
            // Optional supervisory user assignment.
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['cooperative_id', 'name'], 'clusters_cooperative_name_unique');
            $table->index(['cooperative_id', 'status'], 'clusters_cooperative_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clusters');
    }
};
