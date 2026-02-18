<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all admin notifications.
     */
    public function index(Request $request)
    {
        $query = AdminNotification::with(['company', 'reader']);

        // Filter by read status
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
            'unread_count' => AdminNotification::getUnreadCount()
        ]);
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'count' => AdminNotification::getUnreadCount()
            ]
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        AdminNotification::unread()->update([
            'is_read' => true,
            'read_by' => auth()->id(),
            'read_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete notification.
     */
    public function destroy($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification deleted'
        ]);
    }
}
