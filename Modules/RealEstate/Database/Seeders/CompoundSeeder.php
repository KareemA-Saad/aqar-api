<?php

declare(strict_types=1);

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Modules\RealEstate\Entities\Area;
use Modules\RealEstate\Entities\Amenity;
use Modules\RealEstate\Entities\Compound;
use Modules\RealEstate\Entities\Developer;

class CompoundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if required tables have data
        if (Area::count() === 0) {
            $this->command?->warn('No areas found. Please run AreaSeeder first.');
            return;
        }

        // Get areas - use first available area as fallback
        $fallbackArea = Area::first();
        $newCairo = Area::where('slug', 'new-cairo')->first() ?? $fallbackArea;
        $sheikhZayed = Area::where('slug', 'sheikh-zayed')->first() ?? $fallbackArea;
        $sixthOctober = Area::where('slug', '6th-october')->first() ?? $fallbackArea;
        $northCoast = Area::where('slug', 'north-coast')->first() ?? $fallbackArea;
        $ainSokhna = Area::where('slug', 'ain-sokhna')->first() ?? $fallbackArea;
        $maadi = Area::where('slug', 'maadi')->first() ?? $fallbackArea;
        
        $sodic = Developer::where('slug', 'sodic')->first();
        $emaar = Developer::where('slug', 'emaar-misr')->first();
        $palmHills = Developer::where('slug', 'palm-hills')->first();
        $mountainView = Developer::where('slug', 'mountain-view')->first();
        $ora = Developer::where('slug', 'ora-developers')->first();
        $tatweer = Developer::where('slug', 'tatweer-misr')->first();
        $hydePark = Developer::where('slug', 'hyde-park')->first();
        $cityEdge = Developer::where('slug', 'city-edge')->first();
        
        $amenities = Amenity::pluck('id', 'slug');

        $compounds = [
            // New Cairo Compounds
            [
                'area_id' => $newCairo?->id,
                'developer_id' => $emaar?->id,
                'title' => ['en' => 'Mivida', 'ar' => 'ميفيدا'],
                'slug' => 'mivida',
                'description' => [
                    'en' => 'Mivida is a prestigious residential community in New Cairo, developed by Emaar Misr. It offers a blend of modern architecture and green spaces, with world-class amenities and a prime location near the American University in Cairo.',
                    'ar' => 'ميفيدا هو مجتمع سكني راقي في القاهرة الجديدة، تم تطويره بواسطة إعمار مصر. يوفر مزيجًا من العمارة الحديثة والمساحات الخضراء.'
                ],
                'address' => ['en' => 'New Cairo, Cairo, Egypt', 'ar' => 'القاهرة الجديدة، القاهرة، مصر'],
                'latitude' => 30.0286,
                'longitude' => 31.4689,
                'launch_year' => 2014,
                'delivery_year' => 2024,
                'total_area' => 3900000,
                'units_count' => 5000,
                'construction_status' => 'completed',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 100,
                'min_price' => 3500000,
                'max_price' => 25000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'kids-area', 'parks', 'shopping-mall', 'schools', 'mosque'],
            ],
            [
                'area_id' => $newCairo?->id,
                'developer_id' => $hydePark?->id,
                'title' => ['en' => 'Hyde Park New Cairo', 'ar' => 'هايد بارك القاهرة الجديدة'],
                'slug' => 'hyde-park-new-cairo',
                'description' => [
                    'en' => 'Hyde Park New Cairo is a world-class residential development spanning 1,500 acres of lush greenery and modern amenities. Located in the heart of New Cairo, it offers diverse housing options from apartments to villas.',
                    'ar' => 'هايد بارك القاهرة الجديدة هو مشروع سكني عالمي المستوى يمتد على 1500 فدان من المساحات الخضراء.'
                ],
                'address' => ['en' => 'New Cairo, 5th Settlement', 'ar' => 'القاهرة الجديدة، التجمع الخامس'],
                'latitude' => 30.0300,
                'longitude' => 31.4850,
                'launch_year' => 2012,
                'delivery_year' => 2025,
                'total_area' => 6300000,
                'units_count' => 8000,
                'construction_status' => 'under_construction',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 95,
                'min_price' => 2800000,
                'max_price' => 35000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'kids-area', 'tennis-court', 'parks', 'shopping-mall', 'medical-center', 'schools'],
            ],
            [
                'area_id' => $newCairo?->id,
                'developer_id' => $mountainView?->id,
                'title' => ['en' => 'Mountain View iCity', 'ar' => 'ماونتن فيو آي سيتي'],
                'slug' => 'mountain-view-icity',
                'description' => [
                    'en' => 'Mountain View iCity is an innovative residential community that combines modern technology with sustainable living. Features smart home solutions and eco-friendly design.',
                    'ar' => 'ماونتن فيو آي سيتي هو مجتمع سكني مبتكر يجمع بين التكنولوجيا الحديثة والحياة المستدامة.'
                ],
                'address' => ['en' => 'New Cairo, near AUC', 'ar' => 'القاهرة الجديدة، بالقرب من الجامعة الأمريكية'],
                'latitude' => 30.0250,
                'longitude' => 31.4700,
                'launch_year' => 2018,
                'delivery_year' => 2026,
                'total_area' => 2100000,
                'units_count' => 4500,
                'construction_status' => 'under_construction',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 90,
                'min_price' => 4000000,
                'max_price' => 28000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'kids-area', 'parks', 'jogging-track', 'business-center'],
            ],
            [
                'area_id' => $newCairo?->id,
                'developer_id' => $tatweer?->id,
                'title' => ['en' => 'Bloomfields', 'ar' => 'بلوم فيلدز'],
                'slug' => 'bloomfields',
                'description' => [
                    'en' => 'Bloomfields by Tatweer Misr is a vibrant mixed-use development featuring residential, educational, and commercial spaces. It hosts the New Cairo branch of the German University.',
                    'ar' => 'بلوم فيلدز من تطوير مصر هو مشروع متعدد الاستخدامات يضم مساحات سكنية وتعليمية وتجارية.'
                ],
                'address' => ['en' => 'Mostakbal City, New Cairo', 'ar' => 'مدينة المستقبل، القاهرة الجديدة'],
                'latitude' => 30.0400,
                'longitude' => 31.5200,
                'launch_year' => 2019,
                'delivery_year' => 2027,
                'total_area' => 4200000,
                'units_count' => 6000,
                'construction_status' => 'under_construction',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 85,
                'min_price' => 3200000,
                'max_price' => 22000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'kids-area', 'parks', 'schools', 'medical-center'],
            ],
            
            // Sheikh Zayed Compounds
            [
                'area_id' => $sheikhZayed?->id,
                'developer_id' => $sodic?->id,
                'title' => ['en' => 'SODIC West', 'ar' => 'سوديك ويست'],
                'slug' => 'sodic-west',
                'description' => [
                    'en' => 'SODIC West is a flagship development by SODIC, offering premium residential and commercial spaces in the heart of Sheikh Zayed City. Known for its quality construction and community atmosphere.',
                    'ar' => 'سوديك ويست هو مشروع رائد من سوديك، يقدم مساحات سكنية وتجارية متميزة في قلب مدينة الشيخ زايد.'
                ],
                'address' => ['en' => 'Sheikh Zayed City, Giza', 'ar' => 'مدينة الشيخ زايد، الجيزة'],
                'latitude' => 30.0667,
                'longitude' => 31.0167,
                'launch_year' => 2008,
                'delivery_year' => 2023,
                'total_area' => 4600000,
                'units_count' => 5500,
                'construction_status' => 'completed',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 98,
                'min_price' => 4500000,
                'max_price' => 45000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'golf-course', 'tennis-court', 'parks', 'shopping-mall', 'schools'],
            ],
            [
                'area_id' => $sheikhZayed?->id,
                'developer_id' => $palmHills?->id,
                'title' => ['en' => 'Palm Hills October', 'ar' => 'بالم هيلز أكتوبر'],
                'slug' => 'palm-hills-october',
                'description' => [
                    'en' => 'Palm Hills October is a luxurious gated community featuring world-class golf courses, premium villas, and apartments with stunning landscapes.',
                    'ar' => 'بالم هيلز أكتوبر هو مجتمع فاخر مسور يضم ملاعب جولف عالمية وفيلات وشقق فاخرة.'
                ],
                'address' => ['en' => '6th October City, Giza', 'ar' => 'مدينة 6 أكتوبر، الجيزة'],
                'latitude' => 30.0200,
                'longitude' => 30.9800,
                'launch_year' => 2010,
                'delivery_year' => 2024,
                'total_area' => 4200000,
                'units_count' => 4000,
                'construction_status' => 'completed',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 92,
                'min_price' => 5000000,
                'max_price' => 55000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'golf-course', 'tennis-court', 'spa', 'parks', 'shopping-mall', 'schools', 'mosque'],
            ],
            [
                'area_id' => $sheikhZayed?->id,
                'developer_id' => $ora?->id,
                'title' => ['en' => 'Zed West', 'ar' => 'زد ويست'],
                'slug' => 'zed-west',
                'description' => [
                    'en' => 'Zed West by Ora Developers is a premium residential community in Sheikh Zayed, featuring contemporary architecture and innovative urban planning.',
                    'ar' => 'زد ويست من أورا للتطوير هو مجتمع سكني متميز في الشيخ زايد بتصميم معماري معاصر.'
                ],
                'address' => ['en' => 'Sheikh Zayed City, Giza', 'ar' => 'مدينة الشيخ زايد، الجيزة'],
                'latitude' => 30.0550,
                'longitude' => 31.0000,
                'launch_year' => 2020,
                'delivery_year' => 2027,
                'total_area' => 1850000,
                'units_count' => 3500,
                'construction_status' => 'under_construction',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 88,
                'min_price' => 4200000,
                'max_price' => 32000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'kids-area', 'parks', 'jogging-track', 'business-center'],
            ],
            
            // 6th October Compounds
            [
                'area_id' => $sixthOctober?->id,
                'developer_id' => $mountainView?->id,
                'title' => ['en' => 'Mountain View October', 'ar' => 'ماونتن فيو أكتوبر'],
                'slug' => 'mountain-view-october',
                'description' => [
                    'en' => 'Mountain View October is a lifestyle community offering a unique blend of nature and modern living in 6th October City.',
                    'ar' => 'ماونتن فيو أكتوبر هو مجتمع سكني يقدم مزيجًا فريدًا من الطبيعة والحياة العصرية.'
                ],
                'address' => ['en' => '6th October City, Giza', 'ar' => 'مدينة 6 أكتوبر، الجيزة'],
                'latitude' => 29.9800,
                'longitude' => 30.9500,
                'launch_year' => 2015,
                'delivery_year' => 2025,
                'total_area' => 3200000,
                'units_count' => 4800,
                'construction_status' => 'under_construction',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 82,
                'min_price' => 2500000,
                'max_price' => 18000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'kids-area', 'parks', 'jogging-track'],
            ],
            [
                'area_id' => $sixthOctober?->id,
                'developer_id' => $cityEdge?->id,
                'title' => ['en' => 'Etapa', 'ar' => 'إيتابا'],
                'slug' => 'etapa',
                'description' => [
                    'en' => 'Etapa by City Edge is a modern residential project in 6th October, offering affordable luxury with excellent amenities.',
                    'ar' => 'إيتابا من سيتي إيدج هو مشروع سكني حديث في 6 أكتوبر يقدم رفاهية بأسعار معقولة.'
                ],
                'address' => ['en' => '6th October City, Giza', 'ar' => 'مدينة 6 أكتوبر، الجيزة'],
                'latitude' => 29.9650,
                'longitude' => 30.9300,
                'launch_year' => 2019,
                'delivery_year' => 2026,
                'total_area' => 2800000,
                'units_count' => 5200,
                'construction_status' => 'under_construction',
                'is_featured' => false,
                'is_published' => true,
                'priority' => 75,
                'min_price' => 1800000,
                'max_price' => 12000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'kids-area', 'parks'],
            ],
            
            // North Coast Compounds
            [
                'area_id' => $northCoast?->id,
                'developer_id' => $emaar?->id,
                'title' => ['en' => 'Marassi', 'ar' => 'مراسي'],
                'slug' => 'marassi',
                'description' => [
                    'en' => 'Marassi is an iconic Mediterranean-inspired beach destination on the North Coast, developed by Emaar Misr. Features pristine beaches, a marina, and luxury amenities.',
                    'ar' => 'مراسي هي وجهة شاطئية مستوحاة من البحر المتوسط على الساحل الشمالي، تم تطويرها بواسطة إعمار مصر.'
                ],
                'address' => ['en' => 'Sidi Abdel Rahman, North Coast', 'ar' => 'سيدي عبد الرحمن، الساحل الشمالي'],
                'latitude' => 30.9500,
                'longitude' => 28.7000,
                'launch_year' => 2012,
                'delivery_year' => 2024,
                'total_area' => 6700000,
                'units_count' => 7000,
                'construction_status' => 'completed',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 96,
                'min_price' => 4500000,
                'max_price' => 65000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'beach-access', 'spa', 'kids-area', 'tennis-court', 'shopping-mall', 'restaurants'],
            ],
            [
                'area_id' => $northCoast?->id,
                'developer_id' => $sodic?->id,
                'title' => ['en' => 'Caesar', 'ar' => 'قيصر'],
                'slug' => 'caesar',
                'description' => [
                    'en' => 'Caesar by SODIC is a premium beachfront community on the North Coast, featuring exclusive chalets and villas with direct beach access.',
                    'ar' => 'قيصر من سوديك هو مجتمع شاطئي متميز على الساحل الشمالي مع شاليهات وفيلات حصرية.'
                ],
                'address' => ['en' => 'Ras El Hikma, North Coast', 'ar' => 'رأس الحكمة، الساحل الشمالي'],
                'latitude' => 31.0200,
                'longitude' => 28.3500,
                'launch_year' => 2018,
                'delivery_year' => 2026,
                'total_area' => 3200000,
                'units_count' => 2500,
                'construction_status' => 'under_construction',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 86,
                'min_price' => 3800000,
                'max_price' => 42000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'beach-access', 'kids-area', 'tennis-court', 'water-sports'],
            ],
            [
                'area_id' => $northCoast?->id,
                'developer_id' => $mountainView?->id,
                'title' => ['en' => 'Mountain View North Coast', 'ar' => 'ماونتن فيو الساحل الشمالي'],
                'slug' => 'mountain-view-north-coast',
                'description' => [
                    'en' => 'Mountain View North Coast offers a unique beach living experience with innovative design and world-class facilities.',
                    'ar' => 'ماونتن فيو الساحل الشمالي يقدم تجربة حياة شاطئية فريدة مع تصميم مبتكر ومرافق عالمية.'
                ],
                'address' => ['en' => 'Ras El Hikma, North Coast', 'ar' => 'رأس الحكمة، الساحل الشمالي'],
                'latitude' => 31.0100,
                'longitude' => 28.4000,
                'launch_year' => 2017,
                'delivery_year' => 2025,
                'total_area' => 2400000,
                'units_count' => 3200,
                'construction_status' => 'under_construction',
                'is_featured' => false,
                'is_published' => true,
                'priority' => 78,
                'min_price' => 2800000,
                'max_price' => 25000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'beach-access', 'kids-area', 'parks'],
            ],
            
            // Ain Sokhna Compounds
            [
                'area_id' => $ainSokhna?->id,
                'developer_id' => $tatweer?->id,
                'title' => ['en' => 'IL Monte Galala', 'ar' => 'المونت جلالة'],
                'slug' => 'il-monte-galala',
                'description' => [
                    'en' => 'IL Monte Galala is a mountain-side resort community in Ain Sokhna, offering breathtaking Red Sea views and world-class amenities.',
                    'ar' => 'المونت جلالة هو مجتمع منتجعي على سفح الجبل في العين السخنة مع إطلالات خلابة على البحر الأحمر.'
                ],
                'address' => ['en' => 'Galala Mountain, Ain Sokhna', 'ar' => 'جبل الجلالة، العين السخنة'],
                'latitude' => 29.5500,
                'longitude' => 32.3500,
                'launch_year' => 2016,
                'delivery_year' => 2025,
                'total_area' => 5600000,
                'units_count' => 4500,
                'construction_status' => 'under_construction',
                'is_featured' => true,
                'is_published' => true,
                'priority' => 94,
                'min_price' => 3200000,
                'max_price' => 35000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'beach-access', 'spa', 'kids-area', 'golf-course', 'shopping-mall', 'restaurants'],
            ],
            [
                'area_id' => $ainSokhna?->id,
                'developer_id' => $palmHills?->id,
                'title' => ['en' => 'La Vista Bay', 'ar' => 'لافيستا باي'],
                'slug' => 'la-vista-bay',
                'description' => [
                    'en' => 'La Vista Bay is a premium coastal resort in Ain Sokhna, perfect for year-round beachfront living or holiday getaways.',
                    'ar' => 'لافيستا باي هو منتجع ساحلي متميز في العين السخنة، مثالي للعيش على الشاطئ أو العطلات.'
                ],
                'address' => ['en' => 'Ain Sokhna, Suez', 'ar' => 'العين السخنة، السويس'],
                'latitude' => 29.6200,
                'longitude' => 32.4000,
                'launch_year' => 2015,
                'delivery_year' => 2024,
                'total_area' => 1800000,
                'units_count' => 2000,
                'construction_status' => 'completed',
                'is_featured' => false,
                'is_published' => true,
                'priority' => 72,
                'min_price' => 2200000,
                'max_price' => 18000000,
                'price_currency' => 'EGP',
                'amenities' => ['swimming-pool', 'gym', 'security', 'clubhouse', 'beach-access', 'kids-area', 'water-sports'],
            ],
        ];

        foreach ($compounds as $compoundData) {
            $amenitySlugs = $compoundData['amenities'] ?? [];
            unset($compoundData['amenities']);
            
            // Skip if area doesn't exist
            if (empty($compoundData['area_id'])) {
                continue;
            }

            $compound = Compound::updateOrCreate(
                ['slug' => $compoundData['slug']],
                $compoundData
            );

            // Attach amenities
            $amenityIds = [];
            foreach ($amenitySlugs as $slug) {
                if (isset($amenities[$slug])) {
                    $amenityIds[] = $amenities[$slug];
                }
            }
            
            if (!empty($amenityIds)) {
                $compound->amenities()->sync($amenityIds);
            }
        }
    }
}
