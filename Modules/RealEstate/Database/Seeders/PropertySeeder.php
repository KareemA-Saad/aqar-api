<?php

declare(strict_types=1);

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Modules\RealEstate\Entities\Amenity;
use Modules\RealEstate\Entities\Compound;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyType;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if required tables have data
        $compounds = Compound::with('area')->get();
        
        if ($compounds->isEmpty()) {
            $this->command?->warn('No compounds found. Please run CompoundSeeder first.');
            return;
        }

        $propertyTypes = PropertyType::pluck('id', 'slug');
        
        if ($propertyTypes->isEmpty()) {
            $this->command?->warn('No property types found. Please run PropertyTypeSeeder first.');
            return;
        }

        $amenities = Amenity::pluck('id', 'slug');

        // Property templates for different types
        $propertyTemplates = $this->getPropertyTemplates();

        foreach ($compounds as $compound) {
            try {
                // Generate properties for each compound
                $propertyCount = rand(8, 15);
                
                for ($i = 1; $i <= $propertyCount; $i++) {
                    $template = $propertyTemplates[array_rand($propertyTemplates)];
                    $propertyTypeSlug = $template['type'];
                    $propertyTypeId = $propertyTypes[$propertyTypeSlug] ?? $propertyTypes->first();

                    if (!$propertyTypeId) {
                        continue;
                    }

                    $basePrice = $this->calculateBasePrice($compound, $template);
                    $price = $basePrice * (1 + (rand(-10, 20) / 100)); // ±10-20% variation
                    
                    $bedrooms = $template['bedrooms'][array_rand($template['bedrooms'])];
                    $bathrooms = max(1, $bedrooms - rand(0, 1));
                    $area = $template['area_range'][0] + rand(0, $template['area_range'][1] - $template['area_range'][0]);
                    
                    $finishing = ['finished', 'semi_finished', 'unfinished', 'furnished'][array_rand(['finished', 'semi_finished', 'unfinished', 'furnished'])];
                    $view = ['garden', 'pool', 'street', 'city', 'landscape'][array_rand(['garden', 'pool', 'street', 'city', 'landscape'])];
                    
                    $listingType = rand(1, 10) <= 8 ? 'sale' : 'rent';
                    $paymentOption = $listingType === 'sale' ? ['cash', 'installment', 'both'][array_rand(['cash', 'installment', 'both'])] : 'cash';

                    $title = $this->generateTitle($template, $bedrooms, $compound);
                    $slug = $this->generateSlug($compound->slug ?? 'property', $propertyTypeSlug, $i);

                    $installmentDetails = null;
                    if (in_array($paymentOption, ['installment', 'both'])) {
                        $installmentDetails = [
                            'down_payment_percentage' => rand(10, 30),
                        'installment_years' => rand(5, 10),
                        'monthly_installment' => round($price * 0.7 / (rand(5, 10) * 12)),
                    ];
                }

                $deliveryDate = $compound->delivery_year 
                    ? date('Y-m-d', strtotime($compound->delivery_year . '-' . rand(1, 12) . '-' . rand(1, 28)))
                    : null;

                $property = Property::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'compound_id' => $compound->id,
                        'property_type_id' => $propertyTypeId,
                        'title' => [
                            'en' => $title['en'],
                            'ar' => $title['ar'],
                        ],
                        'slug' => $slug,
                        'description' => [
                            'en' => $this->generateDescription($template, $bedrooms, $area, $compound, 'en'),
                            'ar' => $this->generateDescription($template, $bedrooms, $area, $compound, 'ar'),
                        ],
                        'price' => round($price, 2),
                        'currency' => 'EGP',
                        'price_type' => 'total',
                        'listing_type' => $listingType,
                        'payment_option' => $paymentOption,
                        'installment_details' => $installmentDetails,
                        'bedrooms' => $bedrooms,
                        'bathrooms' => $bathrooms,
                        'area' => $area,
                        'area_unit' => 'sqm',
                        'floor_number' => $template['type'] === 'villa' ? null : rand(1, 15),
                        'total_floors' => $template['type'] === 'villa' ? rand(2, 3) : rand(10, 20),
                        'finishing' => $finishing,
                        'view' => $view,
                        'is_available' => rand(1, 10) <= 8, // 80% available
                        'delivery_date' => $deliveryDate,
                        'reference_number' => 'PROP-' . date('Y') . '-' . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT),
                        'is_featured' => rand(1, 10) <= 2, // 20% featured
                        'is_published' => true,
                        'priority' => rand(1, 100),
                        'views_count' => rand(50, 5000),
                        'inquiry_count' => rand(0, 100),
                        'favorites_count' => rand(0, 200),
                    ]
                );

                // Attach amenities based on property type
                $propertyAmenities = $this->getPropertyAmenities($template['type'], $amenities);
                if (!empty($propertyAmenities)) {
                    $property->amenities()->sync($propertyAmenities);
                }
            }
            } catch (\Exception $e) {
                $this->command?->error("Error seeding properties for compound {$compound->slug}: " . $e->getMessage());
                Log::error("PropertySeeder error for compound {$compound->id}: " . $e->getMessage());
                continue;
            }
        }

        // Update compound stats
        foreach ($compounds as $compound) {
            try {
                $compound->update([
                    'properties_count' => $compound->properties()->count(),
                    'available_properties_count' => $compound->properties()->where('is_available', true)->count(),
                ]);
            } catch (\Exception $e) {
                // Silently continue if stats update fails
            }
        }
    }

    /**
     * Get property templates for different types.
     */
    protected function getPropertyTemplates(): array
    {
        return [
            [
                'type' => 'apartment',
                'bedrooms' => [1, 2, 3],
                'area_range' => [80, 180],
                'price_multiplier' => 1.0,
                'name_en' => 'Apartment',
                'name_ar' => 'شقة',
            ],
            [
                'type' => 'apartment',
                'bedrooms' => [2, 3, 4],
                'area_range' => [120, 250],
                'price_multiplier' => 1.2,
                'name_en' => 'Luxury Apartment',
                'name_ar' => 'شقة فاخرة',
            ],
            [
                'type' => 'villa',
                'bedrooms' => [4, 5, 6],
                'area_range' => [300, 600],
                'price_multiplier' => 2.5,
                'name_en' => 'Villa',
                'name_ar' => 'فيلا',
            ],
            [
                'type' => 'villa',
                'bedrooms' => [5, 6, 7],
                'area_range' => [450, 800],
                'price_multiplier' => 3.5,
                'name_en' => 'Standalone Villa',
                'name_ar' => 'فيلا مستقلة',
            ],
            [
                'type' => 'townhouse',
                'bedrooms' => [3, 4, 5],
                'area_range' => [200, 350],
                'price_multiplier' => 1.8,
                'name_en' => 'Townhouse',
                'name_ar' => 'تاون هاوس',
            ],
            [
                'type' => 'twin-house',
                'bedrooms' => [4, 5],
                'area_range' => [280, 450],
                'price_multiplier' => 2.2,
                'name_en' => 'Twin House',
                'name_ar' => 'توين هاوس',
            ],
            [
                'type' => 'duplex',
                'bedrooms' => [3, 4, 5],
                'area_range' => [180, 320],
                'price_multiplier' => 1.5,
                'name_en' => 'Duplex',
                'name_ar' => 'دوبلكس',
            ],
            [
                'type' => 'penthouse',
                'bedrooms' => [3, 4, 5],
                'area_range' => [200, 400],
                'price_multiplier' => 2.0,
                'name_en' => 'Penthouse',
                'name_ar' => 'بنتهاوس',
            ],
            [
                'type' => 'studio',
                'bedrooms' => [0],
                'area_range' => [40, 70],
                'price_multiplier' => 0.6,
                'name_en' => 'Studio',
                'name_ar' => 'ستوديو',
            ],
            [
                'type' => 'chalet',
                'bedrooms' => [1, 2, 3],
                'area_range' => [60, 150],
                'price_multiplier' => 0.9,
                'name_en' => 'Chalet',
                'name_ar' => 'شاليه',
            ],
        ];
    }

    /**
     * Calculate base price based on compound and property type.
     */
    protected function calculateBasePrice(Compound $compound, array $template): float
    {
        $basePerSqm = 25000; // Base price per sqm in EGP
        
        // Adjust based on area
        $areaMultipliers = [
            'new-cairo' => 1.3,
            'sheikh-zayed' => 1.4,
            '6th-october' => 1.0,
            'north-coast' => 1.2,
            'ain-sokhna' => 1.1,
            'maadi' => 1.5,
        ];
        
        $areaSlug = $compound->area?->slug ?? '';
        $areaMultiplier = $areaMultipliers[$areaSlug] ?? 1.0;
        
        // Adjust based on compound priority (higher priority = more expensive)
        $compoundMultiplier = 1 + ($compound->priority / 200);
        
        $avgArea = ($template['area_range'][0] + $template['area_range'][1]) / 2;
        
        return $basePerSqm * $avgArea * $areaMultiplier * $compoundMultiplier * $template['price_multiplier'];
    }

    /**
     * Generate property title.
     */
    protected function generateTitle(array $template, int $bedrooms, Compound $compound): array
    {
        $bedroomText = $bedrooms > 0 ? $bedrooms . ' Bedroom ' : '';
        $bedroomTextAr = $bedrooms > 0 ? $bedrooms . ' غرف نوم ' : '';
        
        $compoundName = is_array($compound->title) 
            ? ($compound->title['en'] ?? $compound->title[array_key_first($compound->title)] ?? 'Compound')
            : $compound->title;
            
        $compoundNameAr = is_array($compound->title) 
            ? ($compound->title['ar'] ?? $compound->title[array_key_first($compound->title)] ?? 'كمبوند')
            : $compound->title;
        
        return [
            'en' => $bedroomText . $template['name_en'] . ' in ' . $compoundName,
            'ar' => $template['name_ar'] . ' ' . $bedroomTextAr . 'في ' . $compoundNameAr,
        ];
    }

    /**
     * Generate property slug.
     */
    protected function generateSlug(string $compoundSlug, string $propertyType, int $index): string
    {
        return $compoundSlug . '-' . $propertyType . '-' . $index . '-' . substr(md5(uniqid()), 0, 6);
    }

    /**
     * Generate property description.
     */
    protected function generateDescription(array $template, int $bedrooms, float $area, Compound $compound, string $lang): string
    {
        $compoundName = is_array($compound->title) 
            ? ($compound->title[$lang] ?? $compound->title[array_key_first($compound->title)] ?? 'Compound')
            : $compound->title;
        
        if ($lang === 'en') {
            $descriptions = [
                "Stunning {$template['name_en']} available in {$compoundName}. This beautiful property features {$bedrooms} bedroom(s) with a total area of {$area} sqm. Enjoy modern finishes, excellent amenities, and a prime location.",
                "Exceptional {$template['name_en']} for sale in the prestigious {$compoundName}. Spanning {$area} sqm with {$bedrooms} bedroom(s), this property offers contemporary design and luxurious living spaces.",
                "Discover this magnificent {$template['name_en']} in {$compoundName}. With {$bedrooms} bedroom(s) and {$area} sqm of living space, it combines comfort with elegance in one of the most sought-after locations.",
                "Premium {$template['name_en']} available in {$compoundName}. Features include {$bedrooms} spacious bedroom(s), {$area} sqm of thoughtfully designed living space, and access to world-class community amenities.",
            ];
        } else {
            $descriptions = [
                "{$template['name_ar']} مذهلة متاحة في {$compoundName}. هذا العقار الجميل يتميز بـ {$bedrooms} غرفة نوم بمساحة إجمالية {$area} متر مربع. استمتع بالتشطيبات الحديثة والمرافق الممتازة.",
                "{$template['name_ar']} استثنائية للبيع في {$compoundName} المرموق. تمتد على {$area} متر مربع مع {$bedrooms} غرفة نوم، يوفر هذا العقار تصميمًا معاصرًا ومساحات معيشة فاخرة.",
                "اكتشف هذه {$template['name_ar']} الرائعة في {$compoundName}. مع {$bedrooms} غرفة نوم و {$area} متر مربع من مساحة المعيشة، تجمع بين الراحة والأناقة.",
                "{$template['name_ar']} فاخرة متاحة في {$compoundName}. تشمل الميزات {$bedrooms} غرفة نوم واسعة، {$area} متر مربع من مساحة المعيشة المصممة بعناية.",
            ];
        }

        return $descriptions[array_rand($descriptions)];
    }

    /**
     * Get amenities for a property based on type.
     */
    protected function getPropertyAmenities(string $propertyType, $amenities): array
    {
        $propertyAmenityMap = [
            'apartment' => ['air-conditioning', 'balcony', 'parking', 'intercom', 'elevator'],
            'villa' => ['air-conditioning', 'private-garden', 'private-pool', 'parking', 'maid-room', 'driver-room'],
            'townhouse' => ['air-conditioning', 'private-garden', 'parking', 'maid-room'],
            'twin-house' => ['air-conditioning', 'private-garden', 'parking', 'maid-room'],
            'duplex' => ['air-conditioning', 'balcony', 'parking', 'maid-room', 'elevator'],
            'penthouse' => ['air-conditioning', 'terrace', 'parking', 'private-pool', 'elevator', 'panoramic-view'],
            'studio' => ['air-conditioning', 'parking', 'intercom'],
            'chalet' => ['air-conditioning', 'beach-access', 'parking'],
        ];

        $slugs = $propertyAmenityMap[$propertyType] ?? ['air-conditioning', 'parking'];
        $ids = [];

        foreach ($slugs as $slug) {
            if (isset($amenities[$slug])) {
                $ids[] = $amenities[$slug];
            }
        }

        // Add some random common amenities
        $commonAmenities = ['storage', 'laundry-room', 'built-in-wardrobes'];
        foreach ($commonAmenities as $slug) {
            if (isset($amenities[$slug]) && rand(1, 10) <= 5) {
                $ids[] = $amenities[$slug];
            }
        }

        return array_unique($ids);
    }
}
