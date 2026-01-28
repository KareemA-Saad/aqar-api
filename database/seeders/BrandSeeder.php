<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run()
    {
        $brands = [
            [
                'url' => '#',
                'image' => null, // Will be populated with actual brand logos later
                'status' => 1,
            ],
            [
                'url' => '#',
                'image' => null,
                'status' => 1,
            ],
            [
                'url' => '#',
                'image' => null,
                'status' => 1,
            ],
            [
                'url' => '#',
                'image' => null,
                'status' => 1,
            ],
            [
                'url' => '#',
                'image' => null,
                'status' => 1,
            ],
            [
                'url' => '#',
                'image' => null,
                'status' => 1,
            ]
        ];

        foreach ($brands as $brand) {
            Brand::create([
                'url' => $brand['url'],
                'image' => $brand['image'],
                'status' => $brand['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}