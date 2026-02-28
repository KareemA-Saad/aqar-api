<?php

declare(strict_types=1);

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Modules\RealEstate\Entities\SavedSearch;
use Modules\RealEstate\Entities\Area;
use Modules\RealEstate\Entities\PropertyType;

class SavedSearchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates sample saved searches for demo users.
     */
    public function run(): void
    {
        Log::info('SavedSearchSeeder: Starting saved search seeding...');

        // Get some sample areas and property types
        $areas = Area::where('parent_id', null)->limit(5)->get();
        $propertyTypes = PropertyType::limit(5)->get();

        if ($areas->isEmpty() || $propertyTypes->isEmpty()) {
            $this->command?->warn('SavedSearchSeeder: Not enough areas or property types. Skipping seeding.');
            Log::warning('SavedSearchSeeder: Skipped - insufficient lookup data', [
                'areas_count' => $areas->count(),
                'property_types_count' => $propertyTypes->count(),
            ]);
            return;
        }

        // Get or create a demo user for seeding (if needed)
        $demoUser = User::firstOrCreate(
            ['email' => 'demo.buyer@example.com'],
            [
                'name' => 'Demo Buyer',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info("Using user: {$demoUser->name} ({$demoUser->id})");

        // Define sample searches
        $searches = [
            // 1. Budget apartment in New Cairo
            [
                'user_id' => $demoUser->id,
                'name' => 'Affordable Apartments - New Cairo',
                'criteria' => [
                    'area_id' => $areas->first()->id,
                    'listing_type' => 'sale',
                    'price_min' => 1000000,
                    'price_max' => 3000000,
                    'bedrooms_min' => 1,
                    'bedrooms_max' => 3,
                    'area_min' => 80,
                    'area_max' => 150,
                ],
                'alerts_enabled' => true,
                'alert_frequency' => 'daily',
            ],

            // 2. Luxury villas
            [
                'user_id' => $demoUser->id,
                'name' => 'Luxury Villas',
                'criteria' => [
                    'listing_type' => 'sale',
                    'price_min' => 10000000,
                    'bedrooms_min' => 4,
                    'payment_option' => 'cash',
                    'is_featured' => true,
                ],
                'alerts_enabled' => true,
                'alert_frequency' => 'weekly',
            ],

            // 3. Rental apartments
            [
                'user_id' => $demoUser->id,
                'name' => 'Furnished Rentals',
                'criteria' => [
                    'listing_type' => 'rent',
                    'bedrooms' => 2,
                    'finishing' => 'furnished',
                    'price_min' => 5000,
                    'price_max' => 15000,
                ],
                'alerts_enabled' => false,
                'alert_frequency' => 'daily',
            ],

            // 4. Investment properties
            [
                'user_id' => $demoUser->id,
                'name' => 'Investment Properties',
                'criteria' => [
                    'listing_type' => 'sale',
                    'price_max' => 5000000,
                    'payment_option' => 'installment',
                ],
                'alerts_enabled' => true,
                'alert_frequency' => 'weekly',
            ],

            // 5. Studio apartments
            [
                'user_id' => $demoUser->id,
                'name' => 'Studio Apartments - First Property',
                'criteria' => [
                    'bedrooms' => 0,
                    'listing_type' => 'sale',
                    'area_min' => 30,
                    'area_max' => 60,
                    'price_min' => 500000,
                    'price_max' => 1500000,
                ],
                'alerts_enabled' => true,
                'alert_frequency' => 'daily',
            ],
        ];

        // Create saved searches
        foreach ($searches as $searchData) {
            try {
                // Check if search already exists (by user + name)
                $exists = SavedSearch::where('user_id', $searchData['user_id'])
                    ->whereRaw('LOWER(name) = ?', [strtolower($searchData['name'])])
                    ->exists();

                if ($exists) {
                    $this->command?->line("⊘ Skipped (already exists): {$searchData['name']}");
                    continue;
                }

                SavedSearch::create($searchData);

                Log::info('SavedSearchSeeder: Created search', [
                    'user_id' => $searchData['user_id'],
                    'name' => $searchData['name'],
                    'alerts_enabled' => $searchData['alerts_enabled'],
                ]);

                $this->command?->info("✓ Created: {$searchData['name']}");
            } catch (\Exception $e) {
                Log::error('SavedSearchSeeder: Failed to create search', [
                    'name' => $searchData['name'],
                    'error' => $e->getMessage(),
                ]);

                $this->command?->error("✗ Failed: {$searchData['name']} - {$e->getMessage()}");
            }
        }

        Log::info('SavedSearchSeeder: Completed');
        $this->command?->info('SavedSearchSeeder: Completed successfully');
    }
}
