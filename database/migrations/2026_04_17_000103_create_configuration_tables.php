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
        Schema::create('configuration_values', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 120);
            $table->string('scope_type', 50)->default('global');
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->json('value');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'scope_type', 'scope_id']);
            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('configuration_change_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('configuration_value_id')->constrained('configuration_values')->cascadeOnDelete();
            $table->string('key', 120);
            $table->string('scope_type', 50);
            $table->unsignedBigInteger('scope_id');
            $table->json('previous_value')->nullable();
            $table->json('new_value');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('correlation_id')->nullable();
            $table->timestamp('created_at');

            $table->index(['key', 'scope_type', 'scope_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuration_change_events');
        Schema::dropIfExists('configuration_values');
    }
};
