<?php

declare(strict_types=1);

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RealEstate\Entities\Amenity;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
            [
                'name' => ['en' => 'Jogging Track', 'ar' => 'مسار جري'],
                'slug' => 'jogging-track',
                'icon' => 'running',
                'category' => 'compound',
                'order' => 13,
            ],
            [
                'name' => ['en' => 'Business Center', 'ar' => 'مركز أعمال'],
                'slug' => 'business-center',
                'icon' => 'business',
                'category' => 'compound',
                'order' => 14,
            ],
            [
                'name' => ['en' => 'Spa', 'ar' => 'سبا'],
                'slug' => 'spa',
                'icon' => 'spa',
                'category' => 'compound',
                'order' => 15,
            ],
            [
                'name' => ['en' => 'Beach Access', 'ar' => 'دخول الشاطئ'],
                'slug' => 'beach-access',
                'icon' => 'beach',
                'category' => 'compound',
                'order' => 16,
            ],
            [
                'name' => ['en' => 'Restaurants', 'ar' => 'مطاعم'],
                'slug' => 'restaurants',
                'icon' => 'restaurant',
                'category' => 'compound',
                'order' => 17,
            ],
            [
                'name' => ['en' => 'Water Sports', 'ar' => 'رياضات مائية'],
                'slug' => 'water-sports',
                'icon' => 'water-sports',
                'category' => 'compound',
                'order' => 18,
            ],
            
            // Property amenities
            [
                'name' => ['en' => 'Air Conditioning', 'ar' => 'تكييف'],
                'slug' => 'air-conditioning',
                'icon' => 'ac',
                'category' => 'property',
                'order' => 19,
            ],
            [
                'name' => ['en' => 'Balcony', 'ar' => 'شرفة'],
                'slug' => 'balcony',
                'icon' => 'balcony',
                'category' => 'property',
                'order' => 20,
            ],
            [
                'name' => ['en' => 'Garden', 'ar' => 'حديقة'],
                'slug' => 'garden',
                'icon' => 'garden',
                'category' => 'property',
                'order' => 21,
            ],
            [
                'name' => ['en' => 'Private Garden', 'ar' => 'حديقة خاصة'],
                'slug' => 'private-garden',
                'icon' => 'private-garden',
                'category' => 'property',
                'order' => 22,
            ],
            [
                'name' => ['en' => 'Roof', 'ar' => 'روف'],
                'slug' => 'roof',
                'icon' => 'roof',
                'category' => 'property',
                'order' => 23,
            ],
            [
                'name' => ['en' => 'Terrace', 'ar' => 'تراس'],
                'slug' => 'terrace',
                'icon' => 'terrace',
                'category' => 'property',
                'order' => 24,
            ],
            [
                'name' => ['en' => 'Parking', 'ar' => 'جراج'],
                'slug' => 'parking',
                'icon' => 'parking',
                'category' => 'both',
                'order' => 25,
            ],
            [
                'name' => ['en' => 'Elevator', 'ar' => 'مصعد'],
                'slug' => 'elevator',
                'icon' => 'elevator',
                'category' => 'property',
                'order' => 26,
            ],
            [
                'name' => ['en' => 'Maid Room', 'ar' => 'غرفة خادمة'],
                'slug' => 'maid-room',
                'icon' => 'maid',
                'category' => 'property',
                'order' => 27,
            ],
            [
                'name' => ['en' => 'Driver Room', 'ar' => 'غرفة سائق'],
                'slug' => 'driver-room',
                'icon' => 'driver',
                'category' => 'property',
                'order' => 28,
            ],
            [
                'name' => ['en' => 'Storage Room', 'ar' => 'غرفة تخزين'],
                'slug' => 'storage-room',
                'icon' => 'storage',
                'category' => 'property',
                'order' => 29,
            ],
            [
                'name' => ['en' => 'Storage', 'ar' => 'تخزين'],
                'slug' => 'storage',
                'icon' => 'storage',
                'category' => 'property',
                'order' => 30,
            ],
            [
                'name' => ['en' => 'Smart Home', 'ar' => 'منزل ذكي'],
                'slug' => 'smart-home',
                'icon' => 'smart',
                'category' => 'property',
                'order' => 31,
            ],
            [
                'name' => ['en' => 'Built-in Kitchen', 'ar' => 'مطبخ مجهز'],
                'slug' => 'built-in-kitchen',
                'icon' => 'kitchen',
                'category' => 'property',
                'order' => 32,
            ],
            [
                'name' => ['en' => 'View', 'ar' => 'إطلالة'],
                'slug' => 'view',
                'icon' => 'view',
                'category' => 'property',
                'order' => 33,
            ],
            [
                'name' => ['en' => 'Panoramic View', 'ar' => 'إطلالة بانورامية'],
                'slug' => 'panoramic-view',
                'icon' => 'panoramic',
                'category' => 'property',
                'order' => 34,
            ],
            [
                'name' => ['en' => 'Private Pool', 'ar' => 'حمام سباحة خاص'],
                'slug' => 'private-pool',
                'icon' => 'private-pool',
                'category' => 'property',
                'order' => 35,
            ],
            [
                'name' => ['en' => 'Intercom', 'ar' => 'انتركم'],
                'slug' => 'intercom',
                'icon' => 'intercom',
                'category' => 'property',
                'order' => 36,
            ],
            [
                'name' => ['en' => 'Laundry Room', 'ar' => 'غرفة غسيل'],
                'slug' => 'laundry-room',
                'icon' => 'laundry',
                'category' => 'property',
                'order' => 37,
            ],
            [
                'name' => ['en' => 'Built-in Wardrobes', 'ar' => 'خزائن مدمجة'],
                'slug' => 'built-in-wardrobes',
                'icon' => 'wardrobe',
                'category' => 'property',
                'order' => 38,
            ],
        ];

        foreach ($amenities as $amenity) {
            Amenity::updateOrCreate(
                ['slug' => $amenity['slug']],
                $amenity
            );
        }
    }
}
