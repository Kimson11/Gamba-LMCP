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
        Schema::create('milk_production_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->foreignId('cluster_id')->constrained('clusters')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('quantity_liters', 10, 2);
            $table->date('production_date');
            $table->string('source')->default('mobile');
            $table->string('status')->default('recorded');
            $table->timestamps();

            $table->index(['cluster_id', 'production_date'], 'milk_logs_cluster_production_date_idx');
            $table->index(['member_id', 'production_date'], 'milk_logs_member_production_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milk_production_logs');
    }
};
