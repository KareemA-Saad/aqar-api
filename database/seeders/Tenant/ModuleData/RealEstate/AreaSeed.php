<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant\ModuleData\RealEstate;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\RealEstate\Entities\Area;

/**
 * AreaSeed - Seeds areas/locations for RealEstate module
 * 
 * This seeder runs during tenant database creation when the tenant's plan
 * includes RealEstate features (properties, compounds, realestate).
 * 
 * Seeds Egypt's main governorates, cities, and districts for real estate listings.
 */
class AreaSeed
{
    /**
     * Execute the seeder.
     */
    public static function execute(): void
    {
        // Check if the table exists (migrations should have run first)
        if (!Schema::hasTable('re_areas')) {
            Log::warning('AreaSeed: re_areas table does not exist, skipping');
            return;
        }

        // Check if already seeded
        if (Area::count() > 0) {
            Log::info('AreaSeed: Areas already exist, skipping');
            return;
        }

        // Egypt - Main Governorates/Cities (Based on Nawy.com structure)
        $areas = [
            // Greater Cairo
            [
                'name' => ['en' => 'Cairo', 'ar' => 'القاهرة'],
                'slug' => 'cairo',
                'type' => 'governorate',
                'is_featured' => true,
                'order' => 1,
                'children' => [
                    [
                        'name' => ['en' => 'New Cairo', 'ar' => 'القاهرة الجديدة'],
                        'slug' => 'new-cairo',
                        'type' => 'city',
                        'is_featured' => true,
                        'order' => 1,
                    ],
                    [
                        'name' => ['en' => 'Maadi', 'ar' => 'المعادي'],
                        'slug' => 'maadi',
                        'type' => 'district',
                        'is_featured' => true,
                        'order' => 2,
                    ],
                    [
                        'name' => ['en' => 'Nasr City', 'ar' => 'مدينة نصر'],
                        'slug' => 'nasr-city',
                        'type' => 'district',
                        'order' => 3,
                    ],
                    [
                        'name' => ['en' => 'Heliopolis', 'ar' => 'مصر الجديدة'],
                        'slug' => 'heliopolis',
                        'type' => 'district',
                        'order' => 4,
                    ],
                    [
                        'name' => ['en' => 'Zamalek', 'ar' => 'الزمالك'],
                        'slug' => 'zamalek',
                        'type' => 'district',
                        'order' => 5,
                    ],
                ],
            ],
            // Giza
            [
                'name' => ['en' => 'Giza', 'ar' => 'الجيزة'],
                'slug' => 'giza',
                'type' => 'governorate',
                'is_featured' => true,
                'order' => 2,
                'children' => [
                    [
                        'name' => ['en' => '6th October City', 'ar' => 'مدينة 6 أكتوبر'],
                        'slug' => '6th-october',
                        'type' => 'city',
                        'is_featured' => true,
                        'order' => 1,
                    ],
                    [
                        'name' => ['en' => 'Sheikh Zayed', 'ar' => 'الشيخ زايد'],
                        'slug' => 'sheikh-zayed',
                        'type' => 'city',
                        'is_featured' => true,
                        'order' => 2,
                    ],
                    [
                        'name' => ['en' => 'Hadayek October', 'ar' => 'حدائق أكتوبر'],
                        'slug' => 'hadayek-october',
                        'type' => 'city',
                        'order' => 3,
                    ],
                    [
                        'name' => ['en' => 'Dokki', 'ar' => 'الدقي'],
                        'slug' => 'dokki',
                        'type' => 'district',
                        'order' => 4,
                    ],
                    [
                        'name' => ['en' => 'Mohandessin', 'ar' => 'المهندسين'],
                        'slug' => 'mohandessin',
                        'type' => 'district',
                        'order' => 5,
                    ],
                ],
            ],
            // New Administrative Capital
            [
                'name' => ['en' => 'New Administrative Capital', 'ar' => 'العاصمة الإدارية الجديدة'],
                'slug' => 'new-capital',
                'type' => 'city',
                'is_featured' => true,
                'order' => 3,
                'children' => [
                    [
                        'name' => ['en' => 'R7', 'ar' => 'الحي السكني السابع'],
                        'slug' => 'r7',
                        'type' => 'district',
                        'order' => 1,
                    ],
                    [
                        'name' => ['en' => 'R8', 'ar' => 'الحي السكني الثامن'],
                        'slug' => 'r8',
                        'type' => 'district',
                        'order' => 2,
                    ],
                    [
                        'name' => ['en' => 'Downtown', 'ar' => 'داون تاون'],
                        'slug' => 'new-capital-downtown',
                        'type' => 'district',
                        'order' => 3,
                    ],
                ],
            ],
            // North Coast
            [
                'name' => ['en' => 'North Coast', 'ar' => 'الساحل الشمالي'],
                'slug' => 'north-coast',
                'type' => 'region',
                'is_featured' => true,
                'order' => 4,
                'children' => [
                    [
                        'name' => ['en' => 'Sidi Abdel Rahman', 'ar' => 'سيدي عبد الرحمن'],
                        'slug' => 'sidi-abdel-rahman',
                        'type' => 'area',
                        'is_featured' => true,
                        'order' => 1,
                    ],
                    [
                        'name' => ['en' => 'Ras El Hekma', 'ar' => 'رأس الحكمة'],
                        'slug' => 'ras-el-hekma',
                        'type' => 'area',
                        'is_featured' => true,
                        'order' => 2,
                    ],
                    [
                        'name' => ['en' => 'Alamein', 'ar' => 'العلمين'],
                        'slug' => 'alamein',
                        'type' => 'city',
                        'is_featured' => true,
                        'order' => 3,
                    ],
                ],
            ],
            // Ain Sokhna
            [
                'name' => ['en' => 'Ain Sokhna', 'ar' => 'العين السخنة'],
                'slug' => 'ain-sokhna',
                'type' => 'city',
                'is_featured' => true,
                'order' => 5,
            ],
            // Red Sea
            [
                'name' => ['en' => 'Red Sea', 'ar' => 'البحر الأحمر'],
                'slug' => 'red-sea',
                'type' => 'governorate',
                'order' => 6,
                'children' => [
                    [
                        'name' => ['en' => 'Hurghada', 'ar' => 'الغردقة'],
                        'slug' => 'hurghada',
                        'type' => 'city',
                        'is_featured' => true,
                        'order' => 1,
                    ],
                    [
                        'name' => ['en' => 'El Gouna', 'ar' => 'الجونة'],
                        'slug' => 'el-gouna',
                        'type' => 'city',
                        'is_featured' => true,
                        'order' => 2,
                    ],
                ],
            ],
            // Alexandria
            [
                'name' => ['en' => 'Alexandria', 'ar' => 'الإسكندرية'],
                'slug' => 'alexandria',
                'type' => 'governorate',
                'is_featured' => true,
                'order' => 7,
                'children' => [
                    [
                        'name' => ['en' => 'San Stefano', 'ar' => 'سان ستيفانو'],
                        'slug' => 'san-stefano',
                        'type' => 'district',
                        'order' => 1,
                    ],
                    [
                        'name' => ['en' => 'Smouha', 'ar' => 'سموحة'],
                        'slug' => 'smouha',
                        'type' => 'district',
                        'order' => 2,
                    ],
                ],
            ],
        ];

        foreach ($areas as $areaData) {
            self::createArea($areaData);
        }

        Log::info('AreaSeed: Seeded ' . Area::count() . ' areas');
    }

    /**
     * Create area and its children recursively.
     */
    private static function createArea(array $data, ?int $parentId = null): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $data['parent_id'] = $parentId;
        $data['status'] = true;

        $area = Area::updateOrCreate(
            ['slug' => $data['slug']],
            $data
        );

        foreach ($children as $childData) {
            self::createArea($childData, $area->id);
        }
    }

    /**
     * Alias for execute() for compatibility with different seeder patterns.
     */
    public static function run(): void
    {
        self::execute();
    }
}
