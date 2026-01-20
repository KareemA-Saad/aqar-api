<?php

declare(strict_types=1);

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RealEstate\Entities\Developer;

class DeveloperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Top Real Estate Developers in Egypt (Based on Nawy.com)
        $developers = [
            [
                'name' => ['en' => 'SODIC', 'ar' => 'سوديك'],
                'slug' => 'sodic',
                'description' => ['en' => 'SODIC is one of Egypt\'s leading real estate development companies, building world-class destinations that communities are proud to call home.', 'ar' => 'سوديك هي واحدة من الشركات الرائدة في مجال التطوير العقاري في مصر'],
                'logo' => 'developers/sodic.png',
                'website' => 'https://www.sodic.com',
                'is_featured' => true,
            ],
            [
                'name' => ['en' => 'Emaar Misr', 'ar' => 'إعمار مصر'],
                'slug' => 'emaar-misr',
                'description' => ['en' => 'Emaar Misr is a leading developer of integrated lifestyle communities.', 'ar' => 'إعمار مصر من الشركات الرائدة في تطوير المجتمعات المتكاملة'],
                'logo' => 'developers/emaar.png',
                'website' => 'https://www.emaarmisr.com',
                'is_featured' => true,
            ],
            [
                'name' => ['en' => 'Palm Hills', 'ar' => 'بالم هيلز'],
                'slug' => 'palm-hills',
                'description' => ['en' => 'Palm Hills Developments is a leading real estate developer in Egypt and the Middle East.', 'ar' => 'بالم هيلز للتطوير العقاري من الشركات الرائدة في مصر والشرق الأوسط'],
                'logo' => 'developers/palm-hills.png',
                'website' => 'https://www.palmhillsdevelopments.com',
                'is_featured' => true,
            ],
            [
                'name' => ['en' => 'Mountain View', 'ar' => 'ماونتن فيو'],
                'slug' => 'mountain-view',
                'description' => ['en' => 'Mountain View is dedicated to creating vibrant communities where people enjoy a balanced and connected lifestyle.', 'ar' => 'ماونتن فيو ملتزمة بإنشاء مجتمعات نابضة بالحياة'],
                'logo' => 'developers/mountain-view.png',
                'website' => 'https://www.mountainview-egypt.com',
                'is_featured' => true,
            ],
            [
                'name' => ['en' => 'Ora Developers', 'ar' => 'أورا للتطوير'],
                'slug' => 'ora-developers',
                'description' => ['en' => 'Ora Developers creates iconic destinations that set new standards for living.', 'ar' => 'أورا للتطوير تنشئ وجهات مميزة تضع معايير جديدة للعيش'],
                'logo' => 'developers/ora.png',
                'website' => 'https://www.oradevelopers.com',
                'is_featured' => true,
            ],
            [
                'name' => ['en' => 'Tatweer Misr', 'ar' => 'تطوير مصر'],
                'slug' => 'tatweer-misr',
                'description' => ['en' => 'Tatweer Misr is one of the fastest growing real estate developers in Egypt.', 'ar' => 'تطوير مصر من أسرع شركات التطوير العقاري نمواً في مصر'],
                'logo' => 'developers/tatweer-misr.png',
                'website' => 'https://tatweermisr.com',
                'is_featured' => true,
            ],
            [
                'name' => ['en' => 'Hyde Park', 'ar' => 'هايد بارك'],
                'slug' => 'hyde-park',
                'description' => ['en' => 'Hyde Park Developments is a prominent real estate developer in Egypt.', 'ar' => 'هايد بارك للتطوير العقاري من الشركات البارزة في مصر'],
                'logo' => 'developers/hyde-park.png',
                'website' => 'https://www.hydeparkdevelopments.com',
                'is_featured' => true,
            ],
            [
                'name' => ['en' => 'City Edge', 'ar' => 'سيتي إيدج'],
                'slug' => 'city-edge',
                'description' => ['en' => 'City Edge Developments is a subsidiary of the Housing and Development Bank.', 'ar' => 'سيتي إيدج للتطوير تابعة لبنك التعمير والإسكان'],
                'logo' => 'developers/city-edge.png',
                'website' => 'https://www.cityedgedevelopments.com',
                'is_featured' => true,
            ],
            [
                'name' => ['en' => 'Inertia', 'ar' => 'إنرشيا'],
                'slug' => 'inertia',
                'description' => ['en' => 'Inertia Egypt creates award-winning developments that redefine modern living.', 'ar' => 'إنرشيا مصر تنشئ مشاريع حائزة على جوائز تعيد تعريف الحياة العصرية'],
                'logo' => 'developers/inertia.png',
                'website' => 'https://www.inertiaegypt.com',
                'is_featured' => true,
            ],
            [
                'name' => ['en' => 'Madinet Masr', 'ar' => 'مدينة نصر للإسكان'],
                'slug' => 'madinet-masr',
                'description' => ['en' => 'Madinet Masr Housing is one of the largest real estate developers in Egypt.', 'ar' => 'مدينة نصر للإسكان والتعمير من أكبر شركات التطوير العقاري في مصر'],
                'logo' => 'developers/madinet-masr.png',
                'website' => 'https://www.madinetmasr.com',
                'is_featured' => false,
            ],
            [
                'name' => ['en' => 'LMD', 'ar' => 'إل إم دي'],
                'slug' => 'lmd',
                'description' => ['en' => 'LMD is a leading real estate developer committed to creating lasting value.', 'ar' => 'إل إم دي شركة رائدة في التطوير العقاري ملتزمة بخلق قيمة دائمة'],
                'logo' => 'developers/lmd.png',
                'website' => 'https://www.lmd.com.eg',
                'is_featured' => false,
            ],
            [
                'name' => ['en' => 'Talaat Moustafa Group', 'ar' => 'مجموعة طلعت مصطفى'],
                'slug' => 'talaat-moustafa',
                'description' => ['en' => 'TMG is the largest integrated real estate developer in the Arab world.', 'ar' => 'مجموعة طلعت مصطفى أكبر مطور عقاري متكامل في العالم العربي'],
                'logo' => 'developers/tmg.png',
                'website' => 'https://www.talaatmoustafa.com',
                'is_featured' => true,
            ],
            [
                'name' => ['en' => 'Misr Italia', 'ar' => 'مصر إيطاليا'],
                'slug' => 'misr-italia',
                'description' => ['en' => 'Misr Italia Properties develops premium residential and commercial projects.', 'ar' => 'مصر إيطاليا للتطوير العقاري تطور مشاريع سكنية وتجارية فاخرة'],
                'logo' => 'developers/misr-italia.png',
                'website' => 'https://www.misritalia.com',
                'is_featured' => false,
            ],
            [
                'name' => ['en' => 'La Vista', 'ar' => 'لافيستا'],
                'slug' => 'la-vista',
                'description' => ['en' => 'La Vista Developments is a pioneer in the coastal and resort real estate market.', 'ar' => 'لافيستا للتطوير رائدة في سوق العقارات الساحلية والمنتجعات'],
                'logo' => 'developers/la-vista.png',
                'website' => 'https://www.lavista.com.eg',
                'is_featured' => false,
            ],
            [
                'name' => ['en' => 'Al Ahly Sabbour', 'ar' => 'الأهلي صبور'],
                'slug' => 'al-ahly-sabbour',
                'description' => ['en' => 'Al Ahly Sabbour Developments is one of the most reputable real estate developers.', 'ar' => 'الأهلي صبور للتطوير العقاري من أكثر المطورين العقاريين سمعة'],
                'logo' => 'developers/al-ahly-sabbour.png',
                'website' => 'https://www.sabbour.com',
                'is_featured' => false,
            ],
        ];

        foreach ($developers as $developer) {
            Developer::updateOrCreate(
                ['slug' => $developer['slug']],
                array_merge($developer, ['status' => true])
            );
        }
    }
}
