<?php

return [
    'name' => env('PORTAL_NAME', 'Muannas Atelier'),
    'legal' => env('PORTAL_LEGAL', 'Muannas Studio FZ L.L.C · www.muannasatelier.com'),
    'url' => env('PORTAL_URL', 'https://portal.muannasatelier.com'),
    'currency' => env('PORTAL_CURRENCY', 'AED'),

    // first admin, used by MuannasSeeder
    'admin_name' => env('PORTAL_ADMIN_NAME', 'Admin'),
    'admin_email' => env('PORTAL_ADMIN_EMAIL'),
    'admin_password' => env('PORTAL_ADMIN_PASSWORD'),

    // uploads (kilobytes)
    'max_image_kb' => 10240,
    'max_file_kb' => 25600,
    'max_post_images' => 10,
    'bulk_max_rows' => 100,

    // days before the due date that the automatic invoice reminder goes out
    'invoice_reminder_days' => 3,
    // an overdue invoice is re-sent every N days until it is paid
    'overdue_repeat_days' => 7,
];
