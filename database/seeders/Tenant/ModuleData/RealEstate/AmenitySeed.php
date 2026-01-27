<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant\ModuleData\RealEstate;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\RealEstate\Entities\Amenity;

/**
 * AmenitySeed - Seeds amenities for RealEstate module
 * 
 * This seeder runs during tenant database creation when the tenant's plan
 * includes RealEstate features (properties, compounds, realestate).
 */
class AmenitySeed
{
    /**
     * Execute the seeder.
     */
    public static function execute(): void
    {
        // Check if the table exists (migrations should have run first)
        if (!Schema::hasTable('re_amenities')) {
            Log::warning('AmenitySeed: re_amenities table does not exist, skipping');
            return;
        }

        // Check if already seeded
        if (Amenity::count() > 0) {
            Log::info('AmenitySeed: Amenities already exist, skipping');
            return;
        }

        $amenities = [
            // Compound amenities
            [
                'name' => ['en' => 'Swimming Pool', 'ar' => 'حمام سباحة'],
                'slug' => 'swimming-pool',
                'icon' => 'pool',
                'category' => 'both',
                'order' => 1,
            ],
            [
                'name' => ['en' => 'Gym', 'ar' => 'صالة رياضية'],
                'slug' => 'gym',
                'icon' => 'fitness',
                'category' => 'compound',
                'order' => 2,
            ],
            [
                'name' => ['en' => 'Security', 'ar' => 'أمن'],
                'slug' => 'security',
                'icon' => 'security',
                'category' => 'compound',
                'order' => 3,
            ],
            [
                'name' => ['en' => 'Clubhouse', 'ar' => 'نادي'],
                'slug' => 'clubhouse',
                'icon' => 'clubhouse',
                'category' => 'compound',
                'order' => 4,
            ],
            [
                'name' => ['en' => 'Kids Area', 'ar' => 'منطقة أطفال'],
                'slug' => 'kids-area',
                'icon' => 'child',
                'category' => 'compound',
                'order' => 5,
            ],
            [
                'name' => ['en' => 'Tennis Court', 'ar' => 'ملعب تنس'],
                'slug' => 'tennis-court',
                'icon' => 'tennis',
                'category' => 'compound',
                'order' => 6,
            ],
            [
                'name' => ['en' => 'Parks', 'ar' => 'حدائق'],
                'slug' => 'parks',
                'icon' => 'park',
                'category' => 'compound',
                'order' => 7,
            ],
            [
                'name' => ['en' => 'Shopping Mall', 'ar' => 'مول تجاري'],
                'slug' => 'shopping-mall',
                'icon' => 'shopping',
                'category' => 'compound',
                'order' => 8,
            ],
            [
                'name' => ['en' => 'Medical Center', 'ar' => 'مركز طبي'],
                'slug' => 'medical-center',
                'icon' => 'medical',
                'category' => 'compound',
                'order' => 9,
            ],
            [
                'name' => ['en' => 'Schools', 'ar' => 'مدارس'],
                'slug' => 'schools',
                'icon' => 'school',
                'category' => 'compound',
                'order' => 10,
            ],
            [
                'name' => ['en' => 'Mosque', 'ar' => 'مسجد'],
                'slug' => 'mosque',
                'icon' => 'mosque',
                'category' => 'compound',
                'order' => 11,
            ],
            [
                'name' => ['en' => 'Golf Course', 'ar' => 'ملعب جولف'],
                'slug' => 'golf-course',
                'icon' => 'golf',
                'category' => 'compound',
                'order' => 12,
            ],

            // Property amenities
            [
                'name' => ['en' => 'Air Conditioning', 'ar' => 'تكييف'],
                'slug' => 'air-conditioning',
                'icon' => 'ac',
                'category' => 'property',
                'order' => 13,
            ],
            [
                'name' => ['en' => 'Balcony', 'ar' => 'شرفة'],
                'slug' => 'balcony',
                'icon' => 'balcony',
                'category' => 'property',
                'order' => 14,
            ],
            [
                'name' => ['en' => 'Garden', 'ar' => 'حديقة'],
                'slug' => 'garden',
                'icon' => 'garden',
                'category' => 'property',
                'order' => 15,
            ],
            [
                'name' => ['en' => 'Roof', 'ar' => 'روف'],
                'slug' => 'roof',
                'icon' => 'roof',
                'category' => 'property',
                'order' => 16,
            ],
            [
                'name' => ['en' => 'Parking', 'ar' => 'جراج'],
                'slug' => 'parking',
                'icon' => 'parking',
                'category' => 'both',
                'order' => 17,
            ],
            [
                'name' => ['en' => 'Elevator', 'ar' => 'مصعد'],
                'slug' => 'elevator',
                'icon' => 'elevator',
                'category' => 'property',
                'order' => 18,
            ],
            [
                'name' => ['en' => 'Maid Room', 'ar' => 'غرفة خادمة'],
                'slug' => 'maid-room',
                'icon' => 'maid',
                'category' => 'property',
                'order' => 19,
            ],
            [
                'name' => ['en' => 'Storage Room', 'ar' => 'غرفة تخزين'],
                'slug' => 'storage-room',
                'icon' => 'storage',
                'category' => 'property',
                'order' => 20,
            ],
            [
                'name' => ['en' => 'Smart Home', 'ar' => 'منزل ذكي'],
                'slug' => 'smart-home',
                'icon' => 'smart',
                'category' => 'property',
                'order' => 21,
            ],
            [
                'name' => ['en' => 'Built-in Kitchen', 'ar' => 'مطبخ مجهز'],
                'slug' => 'built-in-kitchen',
                'icon' => 'kitchen',
                'category' => 'property',
                'order' => 22,
            ],
            [
                'name' => ['en' => 'View', 'ar' => 'إطلالة'],
                'slug' => 'view',
                'icon' => 'view',
                'category' => 'property',
                'order' => 23,
            ],
            [
                'name' => ['en' => 'Private Pool', 'ar' => 'حمام سباحة خاص'],
                'slug' => 'private-pool',
                'icon' => 'private-pool',
                'category' => 'property',
                'order' => 24,
            ],
        ];

        foreach ($amenities as $amenity) {
            Amenity::updateOrCreate(
                ['slug' => $amenity['slug']],
                $amenity
            );
        }

        Log::info('AmenitySeed: Seeded ' . count($amenities) . ' amenities');
    }

    /**
     * Alias for execute() for compatibility with different seeder patterns.
     */
    public static function run(): void
    {
        self::execute();
    }
}
