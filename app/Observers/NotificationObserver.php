<?php

namespace App\Observers;

use App\Mail\NotificationMail;
use App\Models\Notification as AppNotification;
use Illuminate\Support\Facades\Mail;

class NotificationObserver
{
    /**
     * Handle the Notification "created" event.
     * Sends an email to the user's institutional email when they receive an in-app notification.
     */
    public function created(AppNotification $notification): void
    {
        $notification->load('user');

        $user = $notification->user;
        if (!$user || !$user->email) {
            return;
        }

        $viewInSystemUrl = url()->route('student.notifications.index');

        try {
            Mail::to($user->email)->send(
                new NotificationMail(
                    title: $notification->title,
                    body: $notification->message,
                    viewInSystemUrl: $viewInSystemUrl
                )
            );
        } catch (\Throwable $e) {
            // Log the error but don't crash, preventing 500 errors when email fails
            \Illuminate\Support\Facades\Log::error('Failed to queue notification email: ' . $e->getMessage(), [
                'user_id' => $user->user_id,
                'email' => $user->email,
                'notification_id' => $notification->notification_id ?? null,
            ]);
        }
    }
}
