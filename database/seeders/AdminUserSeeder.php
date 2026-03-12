<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $existingAdmin = User::where('username', 'administrator@root')->first();
        
        if (!$existingAdmin) {
            User::create([
                'username' => 'administrator@root',
                'name' => 'System Administrator',
                'email' => 'admin@system.local',
                'password' => Hash::make('F@iz@n!984'),
                'license_key' => User::generateLicenseKey(),
                'is_admin' => true,
                'phone_verified_at' => now(),
            ]);
            
            $this->command->info('Admin user created successfully.');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}
