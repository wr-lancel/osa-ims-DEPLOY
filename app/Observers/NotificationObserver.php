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
        \Illuminate\Support\Facades\Log::info('NotificationObserver fired', [
            'notification_id' => $notification->notification_id ?? null,
            'user_id' => $notification->user_id,
        ]);

        $notification->load('user');

        $user = $notification->user;
        if (!$user || !$user->email) {
            \Illuminate\Support\Facades\Log::warning('NotificationObserver: No user or no email', [
                'user_exists' => !!$user,
                'email' => $user?->email,
                'user_id' => $notification->user_id,
            ]);
            return;
        }

        \Illuminate\Support\Facades\Log::info('NotificationObserver: Sending email', [
            'to' => $user->email,
            'title' => $notification->title,
        ]);

        $viewInSystemUrl = url()->route('student.notifications.index');

        try {
            Mail::to($user->email)->send(
                new NotificationMail(
                    title: $notification->title,
                    body: $notification->message,
                    viewInSystemUrl: $viewInSystemUrl
                )
            );
            \Illuminate\Support\Facades\Log::info('NotificationObserver: Email sent successfully', [
                'to' => $user->email,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send notification email: ' . $e->getMessage(), [
                'user_id' => $user->user_id,
                'email' => $user->email,
                'notification_id' => $notification->notification_id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
