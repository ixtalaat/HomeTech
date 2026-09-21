<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Plumbing',
                'name_ar' => 'سباكة',
                'description' => 'Comprehensive plumbing installations, leak fixes, heater repairs, and drain clearing.',
                'description_ar' => 'تركيبات سباكة شاملة وإصلاح التسريبات والسخانات وتسليك المصارف.',
                'icon' => 'wrench',
            ],
            [
                'name' => 'Electrical',
                'name_ar' => 'كهرباء',
                'description' => 'Expert electrical diagnostics, circuit troubleshooting, rewiring, and fixture setup.',
                'description_ar' => 'تشخيص كهربائي متخصص وإصلاح الدوائر وإعادة التوصيل وتركيب الوحدات.',
                'icon' => 'zap',
            ],
            [
                'name' => 'Air Conditioning',
                'name_ar' => 'تكييف',
                'description' => 'AC unit maintenance, gas refilling, filter deep cleaning, and cooling optimization.',
                'description_ar' => 'صيانة التكييف وشحن الفريون والتنظيف العميق للفلاتر وتحسين التبريد.',
                'icon' => 'snowflake',
            ],
            [
                'name' => 'Painting',
                'name_ar' => 'دهانات',
                'description' => 'Interior and exterior wall painting, plaster repairs, and waterproofing coatings.',
                'description_ar' => 'دهانات داخلية وخارجية وإصلاح المحارة ودهانات العزل.',
                'icon' => 'paint-brush',
            ],
            [
                'name' => 'Appliance Repair',
                'name_ar' => 'صيانة الأجهزة',
                'description' => 'Repair services for washing machines, refrigerators, dishwashers, and ovens.',
                'description_ar' => 'إصلاح الغسالات والثلاجات وغسالات الأطباق والأفران.',
                'icon' => 'cpu',
            ],
        ];

        foreach ($categories as $cat) {
            $category = ServiceCategory::firstOrCreate(
                ['name' => $cat['name']],
                [
                    'slug' => Str::slug($cat['name']),
                    'description' => $cat['description'],
                    'icon' => $cat['icon'],
                    'is_active' => true,
                ]
            );

            $category->saveTranslations(['ar' => [
                'name' => $cat['name_ar'],
                'description' => $cat['description_ar'],
            ]]);
        }
    }
}
