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
        Schema::create('cooperatives', function (Blueprint $table): void {
            $table->id();
            // Human-readable cooperative name used in mobile/web dropdowns.
            $table->string('name');
            // Stable cooperative code used by integrations and exports.
            $table->string('code')->unique();
            // Country isolation anchor for policy and reporting boundaries.
            $table->string('country_code', 2);
            // Lifecycle state for soft operational controls.
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['country_code', 'status'], 'cooperatives_country_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooperatives');
    }
};
