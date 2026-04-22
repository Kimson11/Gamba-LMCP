<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Return the authenticated user's notification inbox.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $actor = $request->user();
        $paginator = $actor->notifications()
            ->latest('created_at')
            ->paginate((int) ($validated['per_page'] ?? 20));

        $deliveryRows = NotificationDelivery::query()
            ->whereIn('notification_id', $paginator->getCollection()->pluck('id')->all())
            ->select(['notification_id', 'status', 'delivered_at', 'failure_reason'])
            ->get()
            ->keyBy('notification_id');

        return ApiResponse::success([
            'items' => $paginator->getCollection()->map(function (DatabaseNotification $notification) use ($deliveryRows): array {
                $delivery = $deliveryRows->get($notification->id);

                return [
                    'id' => $notification->id,
                    'type' => (string) data_get($notification->data, 'type', 'system'),
                    'title' => (string) data_get($notification->data, 'title', ''),
                    'message' => (string) data_get($notification->data, 'message', ''),
                    'payload' => data_get($notification->data, 'payload', []),
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                    'delivery' => [
                        'status' => $delivery?->status,
                        'delivered_at' => $delivery?->delivered_at?->toIso8601String(),
                        'failure_reason' => $delivery?->failure_reason,
                    ],
                ];
            })->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'unread_count' => $actor->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as read for the authenticated user.
     */
    public function markRead(Request $request, string $notification): JsonResponse
    {
        /** @var DatabaseNotification|null $record */
        $record = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->first();

        if ($record === null) {
            return ApiResponse::error(
                message: __('api.errors.notification_not_found'),
                code: 'not_found',
                status: 404,
            );
        }

        if ($record->read_at === null) {
            $record->markAsRead();
            $record = $record->fresh();
        }

        return ApiResponse::success([
            'id' => $record->id,
            'read_at' => $record->read_at?->toIso8601String(),
        ]);
    }
}
