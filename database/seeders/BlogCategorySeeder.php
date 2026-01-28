<?php

namespace Database\Seeders;

use Modules\Blog\Entities\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'title' => ['en_GB' => 'Travel', 'ar' => 'السفر'],
                'status' => 1,
            ],
            [
                'title' => ['en_GB' => 'Technology', 'ar' => 'التكنولوجيا'],
                'status' => 1,
            ],
            [
                'title' => ['en_GB' => 'Business', 'ar' => 'الأعمال'],
                'status' => 1,
            ],
            [
                'title' => ['en_GB' => 'Lifestyle', 'ar' => 'نمط الحياة'],
                'status' => 1,
            ],
            [
                'title' => ['en_GB' => 'Health & Fitness', 'ar' => 'الصحة واللياقة'],
                'status' => 1,
            ],
            [
                'title' => ['en_GB' => 'Education', 'ar' => 'التعليم'],
                'status' => 1,
            ],
            [
                'title' => ['en_GB' => 'Food & Cooking', 'ar' => 'الطعام والطبخ'],
                'status' => 1,
            ],
            [
                'title' => ['en_GB' => 'Entertainment', 'ar' => 'الترفيه'],
                'status' => 1,
            ]
        ];

        foreach ($categories as $category) {
            BlogCategory::create([
                'title' => $category['title'],
                'status' => $category['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}