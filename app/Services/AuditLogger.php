<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Central append-only audit logging service.
 *
 * Every meaningful state change in the platform should be recorded through
 * this service rather than creating AuditLog records directly. This ensures:
 *  - correlation_id is always propagated from the originating request
 *  - actor role is always snapshotted at write time
 *  - context is consistently structured
 *
 * Usage example (inside a controller or action class):
 *
 *   app(AuditLogger::class)->record(
 *       action: 'milk_log.created',
 *       subject: $milkLog,
 *       context: ['quantity_litres' => 12.5, 'cluster_id' => 3],
 *   );
 *
 * This produces an AuditLog row with:
 *   actor_id: <authenticated user id>
 *   actor_role: 'member'     ← snapshotted, survives role changes
 *   action: 'milk_log.created'
 *   subject_type: 'App\Models\MilkLog', subject_id: <id>
 *   correlation_id: <uuid from X-Correlation-Id header>
 *   context: { "quantity_litres": 12.5, "cluster_id": 3 }
 */
class AuditLogger
{
    public function __construct(
        // Laravel's request singleton — injected so we can read correlation IDs
        // set by AttachRequestContext without coupling callers to request() helper.
        private readonly Request $request,
    ) {}

    /**
     * Record a single audit event.
     *
     * @param  string  $action  Dot-notation event name. Convention: '<domain>.<verb>'
     *                          Examples: 'milk_log.created', 'payout.approved', 'user.login'
     * @param  object|null  $subject  The Eloquent model affected (must have an 'id' property).
     *                                Pass null for events not tied to a specific model row.
     * @param  array<string, mixed>  $context  Free-form key-value detail for the event.
     * @param  User|null  $actor  Defaults to the currently authenticated user.
     *                            Pass null explicitly for system-generated events.
     * @param  int|null  $cooperativeId  Cooperative this event belongs to. Null for system events.
     */
    public function record(
        string $action,
        ?object $subject = null,
        array $context = [],
        ?User $actor = null,
        ?int $cooperativeId = null,
    ): AuditLog {
        // Resolve the actor in this order:
        // 1) explicitly passed actor (highest priority)
        // 2) authenticated user attached to the current request
        // 3) authenticated user from the auth manager (covers non-HTTP contexts)
        //
        // Example:
        // - Controller call with logged-in member: resolves to that member.
        // - Scheduled job/system call with no user: resolves to null.
        $resolvedActor = $actor ?? $this->request->user() ?? Auth::user();

        // Snapshot the role string at this exact moment.
        // Even if the user's role changes later, the audit row is immutable.
        // Example: 'member', 'coop_admin', null (for system events)
        $actorRole = $resolvedActor?->role?->value;

        // ── Subject polymorphic columns ──────────────────────────────────
        // We record the class name and primary key so the audit row can be
        // joined back to any model type without a separate lookup table.
        $subjectType = null;
        $subjectId = null;

        if ($subject !== null) {
            // getMorphClass() respects Eloquent morph aliases if registered;
            // falls back to the fully-qualified class name otherwise.
            $subjectType = method_exists($subject, 'getMorphClass')
                ? $subject->getMorphClass()
                : $subject::class;

            $subjectId = $subject->id ?? null;
        }

        // ── Correlation ID ───────────────────────────────────────────────
        // Pulled from request attributes where AttachRequestContext stored it.
        // All audit rows produced by one HTTP request share this UUID, making
        // it easy to reconstruct the full chain of events for any request.
        $correlationId = $this->request->attributes->get('correlation_id');

        // ── Persist (append-only) ────────────────────────────────────────
        // Using create() so mass-assignment protection remains active.
        // NEVER call update() or delete() on AuditLog rows.
        return AuditLog::create([
            'actor_id' => $resolvedActor?->id,
            'actor_role' => $actorRole,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'cooperative_id' => $cooperativeId,
            'correlation_id' => $correlationId,
            // Store null rather than an empty JSON object when there's no detail.
            'context' => $context !== [] ? $context : null,
        ]);
    }
}
