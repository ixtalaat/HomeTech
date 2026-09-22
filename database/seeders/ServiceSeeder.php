<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servicesData = [
            'Plumbing' => [
                [
                    'name' => 'Faucet & Tap Repair',
                    'name_ar' => 'إصلاح الحنفيات والخلاطات',
                    'description' => 'Fix leaking, dripping, or loose faucets and install new mixer taps.',
                    'description_ar' => 'إصلاح الحنفيات المسرّبة والسائبة وتركيب خلاطات جديدة.',
                    'base_price' => 150.00,
                    'estimated_duration_minutes' => 45,
                ],
                [
                    'name' => 'Pipe Leakage Detection & Fix',
                    'name_ar' => 'كشف وإصلاح تسريبات المواسير',
                    'description' => 'Locate and repair burst or hidden pipe leaks with minimal wall intrusion.',
                    'description_ar' => 'تحديد وإصلاح تسريبات المواسير الظاهرة والمخفية بأقل تكسير.',
                    'base_price' => 300.00,
                    'estimated_duration_minutes' => 90,
                ],
                [
                    'name' => 'Water Heater Maintenance',
                    'name_ar' => 'صيانة السخان',
                    'description' => 'Inspect heating element, thermostat calibration, and sediment flushing.',
                    'description_ar' => 'فحص عنصر التسخين ومعايرة الثرموستات وتنظيف الرواسب.',
                    'base_price' => 250.00,
                    'estimated_duration_minutes' => 60,
                ],
                [
                    'name' => 'Drain Unclogging & Cleaning',
                    'name_ar' => 'تسليك وتنظيف المصارف',
                    'description' => 'Deep clear clogged sink, shower, and floor drains using pressure machinery.',
                    'description_ar' => 'تسليك عميق للأحواض والدش وبلاعات الأرضيات بمعدات الضغط.',
                    'base_price' => 200.00,
                    'estimated_duration_minutes' => 60,
                ],
            ],
            'Electrical' => [
                [
                    'name' => 'Circuit Breaker & Short Circuit Repair',
                    'name_ar' => 'إصلاح القواطع والدوائر القصيرة',
                    'description' => 'Emergency troubleshooting of tripped breakers, power surges, and fuse boxes.',
                    'description_ar' => 'معالجة طارئة للقواطع الفاصلة وارتفاعات التيار وعلب الفيوزات.',
                    'base_price' => 250.00,
                    'estimated_duration_minutes' => 60,
                ],
                [
                    'name' => 'Lighting & Chandelier Installation',
                    'name_ar' => 'تركيب الإضاءة والنجف',
                    'description' => 'Mounting, securing, and wiring indoor ceiling lights, spotlights, and chandeliers.',
                    'description_ar' => 'تثبيت وتوصيل إضاءات الأسقف والسبوتات والنجف.',
                    'base_price' => 180.00,
                    'estimated_duration_minutes' => 45,
                ],
                [
                    'name' => 'Wall Socket & Switch Replacement',
                    'name_ar' => 'استبدال البرايز والمفاتيح',
                    'description' => 'Upgrade damaged power sockets, switches, and install USB charging outlets.',
                    'description_ar' => 'ترقية البرايز والمفاتيح التالفة وتركيب مخارج شحن USB.',
                    'base_price' => 120.00,
                    'estimated_duration_minutes' => 30,
                ],
            ],
            'Air Conditioning' => [
                [
                    'name' => 'AC Full Seasonal Maintenance & Cleaning',
                    'name_ar' => 'صيانة موسمية شاملة للمكيف',
                    'description' => 'Filter wash, coil chemical cleansing, drain line flush, and airflow test.',
                    'description_ar' => 'غسيل الفلاتر وتنظيف الكويل كيميائيًا وتسليك الصرف واختبار التبريد.',
                    'base_price' => 350.00,
                    'estimated_duration_minutes' => 90,
                ],
                [
                    'name' => 'AC Freon Gas Refill & Pressure Check',
                    'name_ar' => 'شحن فريون المكيف وفحص الضغط',
                    'description' => 'Check refrigerant leaks, recharge R410A/R22 gas, and test cooling output.',
                    'description_ar' => 'فحص تسريبات الفريون وشحن غاز R410A/R22 واختبار التبريد.',
                    'base_price' => 450.00,
                    'estimated_duration_minutes' => 60,
                ],
                [
                    'name' => 'AC Compressor & Capacitor Diagnosis',
                    'name_ar' => 'تشخيص ضاغط ومكثف المكيف',
                    'description' => 'Diagnose humming, non-cooling compressor units, and replace faulty capacitors.',
                    'description_ar' => 'تشخيص أصوات الضاغط وضعف التبريد واستبدال المكثفات التالفة.',
                    'base_price' => 280.00,
                    'estimated_duration_minutes' => 60,
                ],
            ],
            'Painting' => [
                [
                    'name' => 'Single Room Wall Repainting',
                    'name_ar' => 'دهان غرفة كاملة',
                    'description' => 'Wall preparation, primer coating, and 2-layer high-durability finish paint.',
                    'description_ar' => 'تجهيز الحوائط والبرايمر وطبقتين دهان عالي التحمل.',
                    'base_price' => 800.00,
                    'estimated_duration_minutes' => 240,
                ],
                [
                    'name' => 'Wall Crack Patching & Touch-up',
                    'name_ar' => 'معالجة شروخ الحوائط',
                    'description' => 'Fill cracks, plaster smoothing, sand down imperfections, and color match touch-up.',
                    'description_ar' => 'ملء الشروخ وصنفرة المعجون ومطابقة اللون.',
                    'base_price' => 250.00,
                    'estimated_duration_minutes' => 90,
                ],
            ],
            'Appliance Repair' => [
                [
                    'name' => 'Washing Machine Repair',
                    'name_ar' => 'إصلاح الغسالات',
                    'description' => 'Fix spinning issues, water drainage problems, drum noise, and motor belt faults.',
                    'description_ar' => 'إصلاح مشاكل العصر والصرف وأصوات الحلة وسيور الموتور.',
                    'base_price' => 300.00,
                    'estimated_duration_minutes' => 75,
                ],
                [
                    'name' => 'Refrigerator & Freezer Repair',
                    'name_ar' => 'إصلاح الثلاجات والفريزرات',
                    'description' => 'Solve cooling failures, thermostat defects, door seal leakages, and defrost issues.',
                    'description_ar' => 'حل مشاكل التبريد وعيوب الثرموستات وتسريب الجوانات والنوفروست.',
                    'base_price' => 350.00,
                    'estimated_duration_minutes' => 90,
                ],
            ],
        ];

        foreach ($servicesData as $categoryName => $services) {
            $category = ServiceCategory::where('name', $categoryName)->first();
            if (! $category) {
                continue;
            }

            foreach ($services as $service) {
                $model = Service::firstOrCreate(
                    [
                        'service_category_id' => $category->id,
                        'name' => $service['name'],
                    ],
                    [
                        'slug' => Str::slug($service['name']),
                        'description' => $service['description'],
                        'base_price' => $service['base_price'],
                        'estimated_duration_minutes' => $service['estimated_duration_minutes'],
                        'is_active' => true,
                    ]
                );

                $model->saveTranslations(['ar' => [
                    'name' => $service['name_ar'],
                    'description' => $service['description_ar'],
                ]]);
            }
        }
    }
}
