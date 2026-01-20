<?php

declare(strict_types=1);

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RealEstate\Entities\Area;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
                    [
                        'name' => ['en' => 'Garden City', 'ar' => 'جاردن سيتي'],
                        'slug' => 'garden-city',
                        'type' => 'district',
                        'order' => 6,
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
                    [
                        'name' => ['en' => 'MU23', 'ar' => 'إم يو 23'],
                        'slug' => 'mu23',
                        'type' => 'district',
                        'order' => 4,
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
                    [
                        'name' => ['en' => 'Marina', 'ar' => 'مارينا'],
                        'slug' => 'marina',
                        'type' => 'area',
                        'order' => 4,
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
                    [
                        'name' => ['en' => 'Marsa Alam', 'ar' => 'مرسى علم'],
                        'slug' => 'marsa-alam',
                        'type' => 'city',
                        'order' => 3,
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
                    [
                        'name' => ['en' => 'Roushdy', 'ar' => 'رشدي'],
                        'slug' => 'roushdy',
                        'type' => 'district',
                        'order' => 3,
                    ],
                ],
            ],
        ];

        foreach ($areas as $areaData) {
            $this->createArea($areaData);
        }
    }

    /**
     * Create area and its children recursively.
     */
    private function createArea(array $data, ?int $parentId = null): void
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
            $this->createArea($childData, $area->id);
        }
    }
}
