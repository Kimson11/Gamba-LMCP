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
        // Delivery ledger for notification attempts.
        //
        // This table tracks delivery metadata independent from Laravel's
        // built-in `notifications` table so we can capture operational
        // status (pending/sent/failed), failure reasons, and correlation IDs.
        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();

            // Recipient user for this delivery attempt.
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();

            // If a database notification row is created, store its UUID here.
            $table->uuid('notification_id')->nullable();

            // Logical notification type (domain-level category).
            // Examples: 'approval.pending', 'payout.approved', 'sync.failed'
            $table->string('notification_type');

            // Delivery channel used for this row. Current primitive supports only 'database'.
            $table->string('channel')->default('database');

            // Lifecycle status for operational monitoring.
            // pending -> sent OR failed
            $table->string('status')->default('pending');

            // Human-readable content snapshots for diagnostics.
            $table->string('title');
            $table->text('message');

            // Additional payload data passed to the client.
            $table->json('payload')->nullable();

            // Failure detail if delivery fails.
            $table->text('failure_reason')->nullable();

            // Correlation ID for request traceability.
            $table->uuid('correlation_id')->nullable();

            // When the delivery was marked as sent.
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            // Query optimization indexes.
            $table->index(['recipient_user_id', 'status'], 'notification_deliveries_recipient_status_idx');
            $table->index('notification_type');
            $table->index('correlation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
