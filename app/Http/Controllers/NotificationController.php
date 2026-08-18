<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter'); // unread | read | null

        $notifications = Notification::where('user_id', $request->user()->id)
            ->when($filter === 'unread', fn ($q) => $q->where('is_read', false))
            ->when($filter === 'read',   fn ($q) => $q->where('is_read', true))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $unreadCount = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter'));
    }

    public function markAsRead(Request $request, Notification $notification): RedirectResponse
    {
        // A user may only touch their own notifications
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $updated = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return back()->with('success', $updated . ' notification(s) marked as read.');
    }

    public function destroy(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->delete();

        return back()->with('success', 'Notification deleted.');
    }
}
