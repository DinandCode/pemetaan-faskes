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

// File ini berada di folder /api, sehingga Symfony/Laravel keliru menyimpulkan
// bahwa "/api" adalah base URL dan membuangnya dari path request
// (/api/faskes dibaca sebagai /faskes). Paksa base URL menjadi root.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';

require __DIR__ . '/../public/index.php';