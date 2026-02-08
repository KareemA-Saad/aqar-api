<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant\ModuleData\RealEstate;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\RealEstate\Entities\Developer;

/**
 * DeveloperSeed - Seeds developers for RealEstate module
 * 
 * This seeder runs during tenant database creation when the tenant's plan
 * includes RealEstate features (properties, compounds, realestate).
 */
class DeveloperSeed
{
    /**
     * Execute the seeder.
     */
    public static function execute(): void
    {
        // Check if the table exists (migrations should have run first)
        if (!Schema::hasTable('re_developers')) {
            Log::warning('DeveloperSeed: re_developers table does not exist, skipping');
            return;
        }

        // Check if already seeded
        if (Developer::count() > 0) {
            Log::info('DeveloperSeed: Developers already exist, skipping');
            return;
        }

        $developers = [
            [
                'name' => ['en' => 'Prime Developments', 'ar' => 'برايم للتطوير'],
                'slug' => 'prime-developments',
                'description' => ['en' => 'Leading real estate developer', 'ar' => 'مطور عقاري رائد'],
                'logo' => null,
                'phone' => '+1-555-0100',
                'email' => 'info@primedevelopments.com',
                'website' => 'https://www.primedevelopments.com',
                'address' => ['en' => 'Downtown Business District', 'ar' => 'منطقة الأعمال المركزية'],
                'established_year' => 2010,
                'status' => 1,
            ],
            [
                'name' => ['en' => 'Modern Living Group', 'ar' => 'مجموعة المعيشة العصرية'],
                'slug' => 'modern-living-group',
                'description' => ['en' => 'Innovative residential developments', 'ar' => 'تطويرات سكنية مبتكرة'],
                'logo' => null,
                'phone' => '+1-555-0200',
                'email' => 'contact@modernliving.com',
                'website' => 'https://www.modernliving.com',
                'address' => ['en' => 'New City Center', 'ar' => 'مركز المدينة الجديدة'],
                'established_year' => 2015,
                'status' => 1,
            ],
            [
                'name' => ['en' => 'Elite Properties', 'ar' => 'العقارات المتميزة'],
                'slug' => 'elite-properties',
                'description' => ['en' => 'Luxury real estate specialist', 'ar' => 'متخصص في العقارات الفاخرة'],
                'logo' => null,
                'phone' => '+1-555-0300',
                'email' => 'sales@eliteproperties.com',
                'website' => 'https://www.eliteproperties.com',
                'address' => ['en' => 'Luxury District', 'ar' => 'المنطقة الفاخرة'],
                'established_year' => 2008,
                'status' => 1,
            ],
        ];

        foreach ($developers as $developerData) {
            try {
                Developer::create([
                    'name' => $developerData['name'],
                    'slug' => $developerData['slug'],
                    'description' => $developerData['description'],
                    'logo' => $developerData['logo'],
                    'phone' => $developerData['phone'],
                    'email' => $developerData['email'],
                    'website' => $developerData['website'],
                    'address' => $developerData['address'],
                    'established_year' => $developerData['established_year'],
                    'status' => $developerData['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('DeveloperSeed: Created developer', ['slug' => $developerData['slug']]);
            } catch (\Throwable $e) {
                Log::error('DeveloperSeed: Failed to create developer', [
                    'slug' => $developerData['slug'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('DeveloperSeed: Completed seeding developers');
    }
}