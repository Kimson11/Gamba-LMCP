<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Generic database notification used by the notification dispatcher.
 *
 * This notification intentionally uses only the `database` channel for now,
 * which keeps delivery deterministic in tests and phase-one environments.
 */
class SystemMessageNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $message,
        private readonly array $payload = [],
    ) {}

    /**
     * Only store this notification in the database channel.
     *
     * Example channel output: ['database']
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Database payload exposed to clients.
     *
     * Example JSON:
     * {
     *   "type": "approval.pending",
     *   "title": "Approval Needed",
     *   "message": "Payout #901 needs review",
     *   "payload": { "entity_type": "payout_request", "entity_id": 901 }
     * }
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'payload' => $this->payload,
        ];
    }
}
