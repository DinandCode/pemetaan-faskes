<?php

// Di Vercel filesystem bersifat read-only, hanya /tmp yang bisa ditulis.
$storage = '/tmp/storage';

foreach ([
    'app/public',
    'framework/cache/data',
    'framework/sessions',
    'framework/views',
    'logs',
] as $dir) {
    if (!is_dir("$storage/$dir")) {
        mkdir("$storage/$dir", 0777, true);
    }
}

$_ENV['LARAVEL_STORAGE_PATH'] = $storage;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storage;
putenv("LARAVEL_STORAGE_PATH=$storage");

require __DIR__ . '/../public/index.php';