<?php

use App\Models\SystemSetting;

if (!function_exists('notification_contact_footer')) {
    /**
     * Returns the footer for student-facing notifications by type.
     * Can be overridden via SystemSetting key 'notification_contact_footer_<type>' (e.g. from Settings UI).
     *
     * @param string $type One of: discipline, complaint, org_meeting
     */
    function notification_contact_footer(string $type): string
    {
        $key = 'notification_contact_footer_' . $type;
        $default = config('notifications.contact_footer.' . $type)
            ?? config('notifications.contact_footer.default');

        return SystemSetting::getValue($key, $default);
    }
}
