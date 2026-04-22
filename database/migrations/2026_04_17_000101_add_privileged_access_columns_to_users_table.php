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
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('mfa_enabled')->default(false)->after('role');
            $table->text('mfa_secret_encrypted')->nullable()->after('mfa_enabled');
            $table->timestamp('mfa_secret_rotated_at')->nullable()->after('mfa_secret_encrypted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'mfa_enabled',
                'mfa_secret_encrypted',
                'mfa_secret_rotated_at',
            ]);
        });
    }
};
