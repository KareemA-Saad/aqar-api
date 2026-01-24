<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant\ModuleData\RealEstate;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\RealEstate\Entities\PropertyType;

/**
 * PropertyTypeSeed - Seeds property types for RealEstate module
 * 
 * This seeder runs during tenant database creation when the tenant's plan
 * includes RealEstate features (properties, compounds, realestate).
 */
class PropertyTypeSeed
{
    /**
     * Execute the seeder.
     */
    public static function execute(): void
    {
        // Check if the table exists (migrations should have run first)
        if (!Schema::hasTable('re_property_types')) {
            Log::warning('PropertyTypeSeed: re_property_types table does not exist, skipping');
            return;
        }

        // Check if already seeded
        if (PropertyType::count() > 0) {
            Log::info('PropertyTypeSeed: PropertyTypes already exist, skipping');
            return;
        }

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

        Log::info('PropertyTypeSeed: Seeded ' . count($propertyTypes) . ' property types');
    }

    /**
     * Alias for execute() for compatibility with different seeder patterns.
     */
    public static function run(): void
    {
        self::execute();
    }
}
