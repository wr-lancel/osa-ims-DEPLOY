<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use App\Services\ModuleAuthorizationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->load('roles');
        }

        // Resolve ModuleAuthorizationService from container
        $moduleAuth = app(ModuleAuthorizationService::class);
        $accessibleModules = $user
            ? $moduleAuth->getAccessibleModules($user)
            : [];

        $disciplineNotificationsUnread = 0;
        $complaintNotificationsUnread = 0;
        $totalUnreadNotifications = 0;
        if ($user && $user->hasRole('student')) {
            $disciplineNotificationsUnread = Notification::where('user_id', $user->user_id)
                ->where('type', 'discipline')
                ->where('is_read', false)
                ->count();
            $complaintNotificationsUnread = Notification::where('user_id', $user->user_id)
                ->where('type', 'complaint')
                ->where('is_read', false)
                ->count();
            $totalUnreadNotifications = Notification::where('user_id', $user->user_id)
                ->where('is_read', false)
                ->count();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'user_id' => $user->user_id,
                    'email' => $user->email,
                    'status' => $user->status,
                    'display_name' => $user->display_name,
                    'roles' => $user->roles->pluck('role_name')->toArray(),
                ] : null,
                'accessible_modules' => $accessibleModules,
            ],
            'discipline_notifications_unread' => $disciplineNotificationsUnread,
            'complaint_notifications_unread' => $complaintNotificationsUnread,
            'unread_notifications_count' => $totalUnreadNotifications,
        ];
    }
}
