<?php

namespace App\Http\Controllers\Student;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends \App\Http\Controllers\Controller
{
    /**
     * Display all notifications for the current student.
     */
    public function index(): Response
    {
        $user = Auth::user();

        $notifications = Notification::where('user_id', $user->user_id)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(function ($notification) {
                return [
                    'notification_id' => $notification->notification_id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'is_read' => $notification->is_read,
                    'related_case_id' => $notification->related_case_id,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });

        $unreadCount = Notification::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->count();

        return Inertia::render('Student/Notifications', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|exists:notifications,notification_id',
        ]);

        $user = Auth::user();

        Notification::where('notification_id', $request->notification_id)
            ->where('user_id', $user->user_id)
            ->update(['is_read' => true]);

        return back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();

        Notification::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back();
    }
}
