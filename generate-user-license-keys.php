<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Generating license keys for existing users...\n\n";

$users = User::whereNull('license_key')->get();

if ($users->count() === 0) {
    echo "No users found without license keys.\n";
    exit(0);
}

foreach ($users as $user) {
    $user->license_key = User::generateLicenseKey();
    $user->save();
    
    echo "✓ User: {$user->username} ({$user->name})\n";
    echo "  License Key: {$user->license_key}\n";
    echo "  Devices: {$user->devices()->count()}/10\n\n";
}

echo "Done! Generated {$users->count()} license keys.\n";
