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
        // First, add the 'role' column to the users table.
        // Each user has exactly one role, stored as an enum string value.
        // Example: 'member', 'coop_admin', 'finance_officer'
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('member')->after('email');
        });

        // The 'roles' table acts as the scope-assignment bridge.
        // A user can be assigned to many scopes (cooperative, cluster, country)
        // depending on their role. This is separate from the role column on users
        // to allow multi-scope assignments for supervisors and admins.
        //
        // Example row:
        //   user_id: 42, scope_type: 'cooperative', scope_id: 7
        //   → user 42 can only operate within cooperative #7
        Schema::create('user_scopes', function (Blueprint $table): void {
            $table->id();

            // Which user this scope assignment belongs to.
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Polymorphic scope: 'country' | 'cooperative' | 'cluster'
            // Allows one table to handle all hierarchy levels.
            $table->string('scope_type');  // e.g. 'cooperative'
            $table->unsignedBigInteger('scope_id');  // e.g. 7

            $table->timestamps();

            // A user cannot be assigned the same scope entry twice.
            $table->unique(['user_id', 'scope_type', 'scope_id'], 'user_scopes_unique');

            // Index for fast lookups when checking a user's scope during auth.
            $table->index(['scope_type', 'scope_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_scopes');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }
};
