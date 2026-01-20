<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Theme Seeder
 *
 * Seeds the core themes available for tenants to choose from.
 * These themes represent different business types the platform supports.
 */
class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $themes = [
            [
                'title' => 'Hotel Booking Theme',
                'slug' => 'Theme-hotel-booking',
                'description' => 'Perfect for hotels, resorts, bed & breakfasts, and accommodation businesses. Includes room management, booking calendar, and availability features.',
                'status' => true,
                'is_available' => true,
                'theme_code' => 'hotel-booking',
                'image' => '/themes/previews/hotel-booking.jpg',
                'url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'eCommerce Theme',
                'slug' => 'Theme-eCommerce',
                'description' => 'Ideal for online shops and product-based businesses. Features product catalog, shopping cart, checkout, and inventory management.',
                'status' => true,
                'is_available' => true,
                'theme_code' => 'ecommerce',
                'image' => '/themes/previews/ecommerce.jpg',
                'url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Blog Theme',
                'slug' => 'Theme-blog',
                'description' => 'Clean and modern design for blogs, news sites, and content creators. Supports categories, tags, comments, and multiple post formats.',
                'status' => true,
                'is_available' => true,
                'theme_code' => 'blog',
                'image' => '/themes/previews/blog.jpg',
                'url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Real Estate Theme',
                'slug' => 'Theme-realestate',
                'description' => 'Designed for property listings, real estate agencies, and property management. Features property search, listing management, and agent profiles.',
                'status' => true,
                'is_available' => true,
                'theme_code' => 'realestate',
                'image' => '/themes/previews/realestate.jpg',
                'url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($themes as $themeData) {
            Theme::updateOrCreate(
                ['slug' => $themeData['slug']],
                $themeData
            );
        }

        $this->command->info('Seeded ' . count($themes) . ' themes successfully.');
    }
}
