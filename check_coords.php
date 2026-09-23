<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$faskes = App\Models\Faskes::all();
foreach ($faskes as $f) {
    echo "ID: {$f->id} | {$f->nama} | Kec: {$f->kecamatan} | Lat: {$f->latitude} | Lng: {$f->longitude}\n";
}
