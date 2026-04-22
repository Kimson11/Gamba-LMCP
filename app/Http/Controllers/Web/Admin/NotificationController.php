<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index');
    }

    public function markRead(string $notification): RedirectResponse
    {
        $actor = request()->user();

        abort_unless($actor !== null, 401);

        /** @var DatabaseNotification|null $record */
        $record = $actor->notifications()
            ->where('id', $notification)
            ->first();

        abort_if($record === null, 404);

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        return back()->with('status', 'Notification marked as read.');
    }
}
