<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Admin Seeder
 *
 * Seeds default admin users for the platform.
 */
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin
        $superAdmin = Admin::updateOrCreate(
            ['email' => 'superadmin@aqar.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'superadmin@aqar.com',
                'password' => Hash::make('Admin@123456'),
                'mobile' => '+966500000001',
                'email_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super-admin');

        // Regular Admin
        $admin = Admin::updateOrCreate(
            ['email' => 'admin@aqar.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@aqar.com',
                'password' => Hash::make('Admin@123456'),
                'mobile' => '+966500000002',
                'email_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        $this->command->info('Admin users seeded successfully!');
        $this->command->table(
            ['Name', 'Email', 'Role'],
            [
                ['Super Admin', 'superadmin@aqar.com', 'super-admin'],
                ['Admin', 'admin@aqar.com', 'admin'],
            ]
        );
    }
}
