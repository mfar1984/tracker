<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Device;

echo "Generating license keys for existing devices...\n\n";

$devices = Device::whereNull('license_key')->get();

if ($devices->count() === 0) {
    echo "No devices found without license keys.\n";
    exit(0);
}

foreach ($devices as $device) {
    $device->license_key = Device::generateLicenseKey();
    $device->save();
    
    echo "✓ Device: {$device->name} ({$device->device_id})\n";
    echo "  License Key: {$device->license_key}\n\n";
}

echo "Done! Generated {$devices->count()} license keys.\n";
