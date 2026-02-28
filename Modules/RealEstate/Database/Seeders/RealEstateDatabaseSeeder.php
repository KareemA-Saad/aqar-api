<?php

declare(strict_types=1);

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;

class RealEstateDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: This seeder is designed to run in a tenant database context.
     * Make sure the tenant database connection is active before running.
     */
    public function run(): void
    {
        $this->call([
            // Base/Lookup data - must run first
            PropertyTypeSeeder::class,
            AmenitySeeder::class,
            AreaSeeder::class,
            DeveloperSeeder::class,
            
            // Dependent data - requires base data
            CompoundSeeder::class,
            PropertySeeder::class,
            PropertyImageSeeder::class,
            PropertyInquirySeeder::class,

            // User features - requires users to exist (typically already in DB)
            SavedSearchSeeder::class,
        ]);
    }
}
