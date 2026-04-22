<?php

namespace App\Livewire\Admin;

use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationInbox extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public string $deliveryFilter = 'all';

    public string $search = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDeliveryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function markRead(string $notificationId): void
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 401);

        /** @var DatabaseNotification|null $notification */
        $notification = $actor->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification === null) {
            abort(404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
            session()->flash('status', 'Notification marked as read.');
        }
    }

    public function render(): View
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 401);

        $query = $actor->notifications()
            ->latest('created_at');

        if ($this->statusFilter === 'unread') {
            $query->whereNull('read_at');
        }

        if ($this->statusFilter === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($this->search !== '') {
            $search = '%'.$this->search.'%';

            $query->where(function ($notificationQuery) use ($search): void {
                $notificationQuery
                    ->where('data->title', 'like', $search)
                    ->orWhere('data->message', 'like', $search)
                    ->orWhere('data->type', 'like', $search);
            });
        }

        if ($this->deliveryFilter !== 'all') {
            $query->whereIn('id', NotificationDelivery::query()
                ->where('recipient_user_id', $actor->id)
                ->where('status', $this->deliveryFilter)
                ->select('notification_id'));
        }

        $notifications = $query->paginate(10);

        $deliveryRows = NotificationDelivery::query()
            ->whereIn('notification_id', $notifications->getCollection()->pluck('id')->all())
            ->get()
            ->keyBy('notification_id');

        $deliveryStatuses = NotificationDelivery::query()
            ->where('recipient_user_id', $actor->id)
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('livewire.admin.notification-inbox', [
            'notifications' => $notifications,
            'deliveryRows' => $deliveryRows,
            'deliveryStatuses' => $deliveryStatuses,
            'unreadCount' => $actor->unreadNotifications()->count(),
        ]);
    }
}
