<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification contact footers by type
    |--------------------------------------------------------------------------
    |
    | Each notification type can have its own footer so contact instructions
    | match the context. Override via SystemSetting keys
    | 'notification_contact_footer_<type>' when using a Settings UI.
    |
    */

    'contact_footer' => [
        'discipline' => 'If you believe this is an error or have questions, please contact the Discipline Office or visit the Office of the Student Affairs and Services.',
        'complaint' => 'If you have questions about this complaint, please contact the Discipline Office or visit the Office of the Student Affairs and Services.',
        'org_meeting' => 'For questions about this meeting, please contact your organization officers or the OSA.',
        'default' => 'If you believe this is an error or have questions, please contact the Discipline Office or visit the Office of the Student Affairs and Services.',
    ],

];
