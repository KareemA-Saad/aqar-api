<?php

declare(strict_types=1);

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RealEstate\Entities\PropertyType;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $propertyTypes = [
            [
                'name' => ['en' => 'Apartment', 'ar' => 'شقة'],
                'slug' => 'apartment',
                'icon' => 'apartment',
                'description' => ['en' => 'Residential apartment unit', 'ar' => 'وحدة سكنية'],
                'order' => 1,
            ],
            [
                'name' => ['en' => 'Villa', 'ar' => 'فيلا'],
                'slug' => 'villa',
                'icon' => 'villa',
                'description' => ['en' => 'Standalone villa with garden', 'ar' => 'فيلا مستقلة مع حديقة'],
                'order' => 2,
            ],
            [
                'name' => ['en' => 'Townhouse', 'ar' => 'تاون هاوس'],
                'slug' => 'townhouse',
                'icon' => 'townhouse',
                'description' => ['en' => 'Multi-floor townhouse', 'ar' => 'تاون هاوس متعدد الطوابق'],
                'order' => 3,
            ],
            [
                'name' => ['en' => 'Twin House', 'ar' => 'توين هاوس'],
                'slug' => 'twin-house',
                'icon' => 'twin-house',
                'description' => ['en' => 'Semi-detached house', 'ar' => 'منزل شبه منفصل'],
                'order' => 4,
            ],
            [
                'name' => ['en' => 'Duplex', 'ar' => 'دوبلكس'],
                'slug' => 'duplex',
                'icon' => 'duplex',
                'description' => ['en' => 'Two-floor apartment unit', 'ar' => 'شقة من طابقين'],
                'order' => 5,
            ],
            [
                'name' => ['en' => 'Penthouse', 'ar' => 'بنتهاوس'],
                'slug' => 'penthouse',
                'icon' => 'penthouse',
                'description' => ['en' => 'Top-floor luxury apartment', 'ar' => 'شقة فاخرة في الطابق العلوي'],
                'order' => 6,
            ],
            [
                'name' => ['en' => 'Studio', 'ar' => 'ستوديو'],
                'slug' => 'studio',
                'icon' => 'studio',
                'description' => ['en' => 'Single room studio apartment', 'ar' => 'شقة استوديو غرفة واحدة'],
                'order' => 7,
            ],
            [
                'name' => ['en' => 'Chalet', 'ar' => 'شاليه'],
                'slug' => 'chalet',
                'icon' => 'chalet',
                'description' => ['en' => 'Beach or resort chalet', 'ar' => 'شاليه شاطئي أو منتجع'],
                'order' => 8,
            ],
            [
                'name' => ['en' => 'Office', 'ar' => 'مكتب'],
                'slug' => 'office',
                'icon' => 'office',
                'description' => ['en' => 'Commercial office space', 'ar' => 'مساحة مكتب تجاري'],
                'order' => 9,
            ],
            [
                'name' => ['en' => 'Shop', 'ar' => 'محل'],
                'slug' => 'shop',
                'icon' => 'shop',
                'description' => ['en' => 'Retail shop space', 'ar' => 'مساحة محل تجاري'],
                'order' => 10,
            ],
            [
                'name' => ['en' => 'Land', 'ar' => 'أرض'],
                'slug' => 'land',
                'icon' => 'land',
                'description' => ['en' => 'Land plot for development', 'ar' => 'قطعة أرض للتطوير'],
                'order' => 11,
            ],
            [
                'name' => ['en' => 'Warehouse', 'ar' => 'مخزن'],
                'slug' => 'warehouse',
                'icon' => 'warehouse',
                'description' => ['en' => 'Industrial warehouse', 'ar' => 'مخزن صناعي'],
                'order' => 12,
            ],
        ];

        foreach ($propertyTypes as $type) {
            PropertyType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
