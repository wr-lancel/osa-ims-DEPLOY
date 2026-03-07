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

        Mail::to($user->email)->queue(
            new NotificationMail(
                title: $notification->title,
                body: $notification->message,
                viewInSystemUrl: $viewInSystemUrl
            )
        );
    }
}
