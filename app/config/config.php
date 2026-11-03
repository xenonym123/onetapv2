<?php
// 1TAP settings. Change the database part to match your XAMPP setup.
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'onetap_db',
        'user' => 'root',
        'pass' => '',          // XAMPP's default root password is empty
    ],
    'app' => [
        'name'        => '1TAP',
        'timezone'    => 'Asia/Manila',
        // true  = clean URLs like /passenger/reload (needs mod_rewrite, on by default in XAMPP)
        // false = index.php?url=passenger/reload
        'pretty_urls' => true,
        'qr_window'   => 30,   // seconds before a ride QR changes
        'debug'       => true, // show error details; set to false when you present
    ],
    'reload' => [
        'min'     => 20,
        'max'     => 5000,
        'methods' => ['gcash' => 'GCash', 'maya' => 'Maya', 'store' => 'Partner store', 'kiosk' => '1TAP kiosk'],
    ],
];
