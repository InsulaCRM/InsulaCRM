<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get recent notifications for the bell dropdown (AJAX).
     */
    public function recent()
    {
        $notifications = auth()->user()
            ->notifications()
            ->take(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'data' => $n->data,
                'read' => $n->read_at !== null,
                'time' => $n->created_at->diffForHumans(),
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        $unreadCount = auth()->user()->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Full notifications page.
     */
    public function index(Request $request)
    {
        $query = auth()->user()->notifications();

        $filter = $request->input('filter', 'all');

        if ($filter === 'unread') {
            $query = auth()->user()->unreadNotifications();
        } elseif (in_array($filter, ['leads', 'deals', 'tasks', 'team'])) {
            $typeMap = [
                'leads' => ['App\\Notifications\\LeadAssigned', 'App\\Notifications\\BuyerMatchFound'],
                'deals' => ['App\\Notifications\\DealStageChanged', 'App\\Notifications\\DueDiligenceWarning'],
                'tasks' => ['App\\Notifications\\SequenceStepEmail'],
                'team'  => ['App\\Notifications\\TeamMemberInvited'],
            ];
            if (isset($typeMap[$filter])) {
                $query->whereIn('type', $typeMap[$filter]);
            }
        }

        $notifications = $query->paginate(25);
        $unreadCount = auth()->user()->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'filter', 'unreadCount'));
    }

    /**
     * Mark a single notification as read (AJAX).
     */
    public function markAsRead(string $id)
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read (AJAX).
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
