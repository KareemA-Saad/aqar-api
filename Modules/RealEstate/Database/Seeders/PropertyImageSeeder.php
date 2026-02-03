<?php

declare(strict_types=1);

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Modules\RealEstate\Entities\Compound;
use Modules\RealEstate\Entities\CompoundImage;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyImage;

class PropertyImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed compound images
        $this->seedCompoundImages();
        
        // Seed property images
        $this->seedPropertyImages();
    }

    /**
     * Seed compound images.
     */
    protected function seedCompoundImages(): void
    {
        $compounds = Compound::all();

        if ($compounds->isEmpty()) {
            $this->command?->warn('No compounds found. Skipping compound images.');
            return;
        }

        $imageTypes = ['gallery', 'master_plan', 'unit_plan', 'location_map'];

        foreach ($compounds as $compound) {
            try {
                // Generate 5-10 images per compound
                $imageCount = rand(5, 10);
                
                for ($i = 1; $i <= $imageCount; $i++) {
                    // First image is always gallery, then random types
                    $type = $i === 1 ? 'gallery' : $imageTypes[array_rand($imageTypes)];
                    $compoundSlug = $compound->slug ?? 'compound-' . $compound->id;
                    
                    $compoundTitle = is_array($compound->title) 
                        ? ($compound->title['en'] ?? 'compound') 
                        : ($compound->title ?? 'compound');

                    CompoundImage::updateOrCreate(
                        [
                            'compound_id' => $compound->id,
                            'order' => $i,
                        ],
                        [
                            'compound_id' => $compound->id,
                            'image_path' => "compounds/{$compoundSlug}/image-{$i}.jpg",
                            'type' => $type,
                            'title' => ucfirst(str_replace('_', ' ', $type)) . ' ' . $i,
                            'alt_text' => ucfirst(str_replace('_', ' ', $type)) . ' of ' . $compoundTitle,
                            'order' => $i,
                        ]
                    );
                }
            } catch (\Exception $e) {
                $this->command?->error("Error seeding images for compound {$compound->id}: " . $e->getMessage());
                Log::error("CompoundImageSeeder error: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Seed property images.
     */
    protected function seedPropertyImages(): void
    {
        $properties = Property::all();

        if ($properties->isEmpty()) {
            $this->command?->warn('No properties found. Skipping property images.');
            return;
        }

        $imageCategories = [
            'living_room' => 'Living Room',
            'bedroom' => 'Bedroom',
            'bathroom' => 'Bathroom',
            'kitchen' => 'Kitchen',
            'exterior' => 'Exterior View',
            'balcony' => 'Balcony',
            'reception' => 'Reception',
        ];

        foreach ($properties as $property) {
            try {
                // Generate 4-8 images per property
                $imageCount = rand(4, 8);
                $categories = array_keys($imageCategories);
                $propertySlug = $property->slug ?? 'property-' . $property->id;
                
                $propertyTitle = is_array($property->title) 
                    ? ($property->title['en'] ?? 'property') 
                    : ($property->title ?? 'property');

                for ($i = 1; $i <= $imageCount; $i++) {
                    $category = $categories[array_rand($categories)];
                    $categoryLabel = $imageCategories[$category];
                    $isPrimary = $i === 1;

                    PropertyImage::updateOrCreate(
                        [
                            'property_id' => $property->id,
                            'order' => $i,
                        ],
                        [
                            'property_id' => $property->id,
                            'image_path' => "properties/{$propertySlug}/image-{$i}.jpg",
                            'title' => $categoryLabel . ' ' . $i,
                            'alt_text' => $categoryLabel . ' - ' . $propertyTitle,
                            'is_primary' => $isPrimary,
                            'order' => $i,
                        ]
                    );
                }

                // Update property thumbnail
                $property->update([
                    'thumbnail' => "properties/{$propertySlug}/image-1.jpg",
                ]);
            } catch (\Exception $e) {
                $this->command?->error("Error seeding images for property {$property->id}: " . $e->getMessage());
                Log::error("PropertyImageSeeder error: " . $e->getMessage());
                continue;
            }
        }

        // Update compound thumbnails
        $compounds = Compound::all();
        foreach ($compounds as $compound) {
            try {
                $compoundSlug = $compound->slug ?? 'compound-' . $compound->id;
                $compound->update([
                    'thumbnail' => "compounds/{$compoundSlug}/image-1.jpg",
                ]);
            } catch (\Exception $e) {
                // Silently continue
            }
        }
    }
}
