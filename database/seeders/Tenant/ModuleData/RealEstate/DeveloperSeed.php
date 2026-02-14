<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant\ModuleData\RealEstate;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\RealEstate\Entities\Developer;

/**
 * DeveloperSeed - Seeds developers for RealEstate module
 * 
 * This seeder runs during tenant database creation when the tenant's plan
 * includes RealEstate features (properties, compounds, realestate).
 */
class DeveloperSeed
{
    /**
     * Execute the seeder.
     */
    public static function execute(): void
    {
        // Check if the table exists (migrations should have run first)
        if (!Schema::hasTable('re_developers')) {
            Log::warning('DeveloperSeed: re_developers table does not exist, skipping');
            return;
        }

        // Check if already seeded
        if (Developer::count() > 0) {
            Log::info('DeveloperSeed: Developers already exist, skipping');
            return;
        }

        $developers = [
            [
                'name' => ['en' => 'SODIC', 'ar' => 'سوديك'],
                'slug' => 'sodic',
                'description' => ['en' => 'SODIC is one of Egypt\'s leading real estate development companies, building world-class destinations that communities are proud to call home.', 'ar' => 'سوديك هي واحدة من الشركات الرائدة في مجال التطوير العقاري في مصر'],
                'logo' => 'developers/sodic.png',
                'phone' => '+20-2-3854-0100',
                'email' => 'info@sodic.com',
                'website' => 'https://www.sodic.com',
                'address' => ['en' => 'Cairo, Egypt', 'ar' => 'القاهرة، مصر'],
                'established_year' => 1996,
                'status' => 1,
            ],
            [
                'name' => ['en' => 'Emaar Misr', 'ar' => 'إعمار مصر'],
                'slug' => 'emaar-misr',
                'description' => ['en' => 'Emaar Misr is a leading developer of integrated lifestyle communities.', 'ar' => 'إعمار مصر من الشركات الرائدة في تطوير المجتمعات المتكاملة'],
                'logo' => 'developers/emaar.png',
                'phone' => '+20-2-3854-1000',
                'email' => 'info@emaarmisr.com',
                'website' => 'https://www.emaarmisr.com',
                'address' => ['en' => 'Cairo, Egypt', 'ar' => 'القاهرة، مصر'],
                'established_year' => 2012,
                'status' => 1,
            ],
            [
                'name' => ['en' => 'Palm Hills', 'ar' => 'بالم هيلز'],
                'slug' => 'palm-hills',
                'description' => ['en' => 'Palm Hills Developments is a leading real estate developer in Egypt and the Middle East.', 'ar' => 'بالم هيلز للتطوير العقاري من الشركات الرائدة في مصر والشرق الأوسط'],
                'logo' => 'developers/palm-hills.png',
                'phone' => '+20-2-3854-2000',
                'email' => 'info@palmhillsdevelopments.com',
                'website' => 'https://www.palmhillsdevelopments.com',
                'address' => ['en' => 'Cairo, Egypt', 'ar' => 'القاهرة، مصر'],
                'established_year' => 2005,
                'status' => 1,
            ],
            [
                'name' => ['en' => 'Mountain View', 'ar' => 'ماونتن فيو'],
                'slug' => 'mountain-view',
                'description' => ['en' => 'Mountain View is dedicated to creating vibrant communities where people enjoy a balanced and connected lifestyle.', 'ar' => 'ماونتن فيو ملتزمة بإنشاء مجتمعات نابضة بالحياة'],
                'logo' => 'developers/mountain-view.png',
                'phone' => '+20-2-3854-3000',
                'email' => 'info@mountainview-egypt.com',
                'website' => 'https://www.mountainview-egypt.com',
                'address' => ['en' => 'Cairo, Egypt', 'ar' => 'القاهرة، مصر'],
                'established_year' => 1998,
                'status' => 1,
            ],
            [
                'name' => ['en' => 'Ora Developers', 'ar' => 'أورا للتطوير'],
                'slug' => 'ora-developers',
                'description' => ['en' => 'Ora Developers creates iconic destinations that set new standards for living.', 'ar' => 'أورا للتطوير تنشئ وجهات مميزة تضع معايير جديدة للعيش'],
                'logo' => 'developers/ora.png',
                'phone' => '+20-2-3854-4000',
                'email' => 'info@oradevelopers.com',
                'website' => 'https://www.oradevelopers.com',
                'address' => ['en' => 'Cairo, Egypt', 'ar' => 'القاهرة، مصر'],
                'established_year' => 2014,
                'status' => 1,
            ],
            [
                'name' => ['en' => 'Tatweer Misr', 'ar' => 'تطوير مصر'],
                'slug' => 'tatweer-misr',
                'description' => ['en' => 'Tatweer Misr is one of the fastest growing real estate developers in Egypt.', 'ar' => 'تطوير مصر من أسرع شركات التطوير العقاري نمواً في مصر'],
                'logo' => 'developers/tatweer-misr.png',
                'phone' => '+20-2-3854-5000',
                'email' => 'info@tatweermisr.com',
                'website' => 'https://tatweermisr.com',
                'address' => ['en' => 'Cairo, Egypt', 'ar' => 'القاهرة، مصر'],
                'established_year' => 2014,
                'status' => 1,
            ],
            [
                'name' => ['en' => 'Hyde Park', 'ar' => 'هايد بارك'],
                'slug' => 'hyde-park',
                'description' => ['en' => 'Hyde Park Developments is a prominent real estate developer in Egypt.', 'ar' => 'هايد بارك للتطوير العقاري من الشركات البارزة في مصر'],
                'logo' => 'developers/hyde-park.png',
                'phone' => '+20-2-3854-6000',
                'email' => 'info@hydeparkdevelopments.com',
                'website' => 'https://www.hydeparkdevelopments.com',
                'address' => ['en' => 'Cairo, Egypt', 'ar' => 'القاهرة، مصر'],
                'established_year' => 2007,
                'status' => 1,
            ],
            [
                'name' => ['en' => 'City Edge', 'ar' => 'سيتي إيدج'],
                'slug' => 'city-edge',
                'description' => ['en' => 'City Edge Developments is a subsidiary of the Housing and Development Bank.', 'ar' => 'سيتي إيدج للتطوير تابعة لبنك التعمير والإسكان'],
                'logo' => 'developers/city-edge.png',
                'phone' => '+20-2-3854-7000',
                'email' => 'info@cityedgedevelopments.com',
                'website' => 'https://www.cityedgedevelopments.com',
                'address' => ['en' => 'Cairo, Egypt', 'ar' => 'القاهرة، مصر'],
                'established_year' => 2015,
                'status' => 1,
            ],
        ];

        foreach ($developers as $developerData) {
            try {
                Developer::create([
                    'name' => $developerData['name'],
                    'slug' => $developerData['slug'],
                    'description' => $developerData['description'],
                    'logo' => $developerData['logo'],
                    'phone' => $developerData['phone'],
                    'email' => $developerData['email'],
                    'website' => $developerData['website'],
                    'address' => $developerData['address'],
                    'established_year' => $developerData['established_year'],
                    'status' => $developerData['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('DeveloperSeed: Created developer', ['slug' => $developerData['slug']]);
            } catch (\Throwable $e) {
                Log::error('DeveloperSeed: Failed to create developer', [
                    'slug' => $developerData['slug'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('DeveloperSeed: Completed seeding developers');
    }
}