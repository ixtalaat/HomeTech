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
                    'description' => 'Fix leaking, dripping, or loose faucets and install new mixer taps.',
                    'base_price' => 150.00,
                    'estimated_duration_minutes' => 45,
                ],
                [
                    'name' => 'Pipe Leakage Detection & Fix',
                    'description' => 'Locate and repair burst or hidden pipe leaks with minimal wall intrusion.',
                    'base_price' => 300.00,
                    'estimated_duration_minutes' => 90,
                ],
                [
                    'name' => 'Water Heater Maintenance',
                    'description' => 'Inspect heating element, thermostat calibration, and sediment flushing.',
                    'base_price' => 250.00,
                    'estimated_duration_minutes' => 60,
                ],
                [
                    'name' => 'Drain Unclogging & Cleaning',
                    'description' => 'Deep clear clogged sink, shower, and floor drains using pressure machinery.',
                    'base_price' => 200.00,
                    'estimated_duration_minutes' => 60,
                ],
            ],
            'Electrical' => [
                [
                    'name' => 'Circuit Breaker & Short Circuit Repair',
                    'description' => 'Emergency troubleshooting of tripped breakers, power surges, and fuse boxes.',
                    'base_price' => 250.00,
                    'estimated_duration_minutes' => 60,
                ],
                [
                    'name' => 'Lighting & Chandelier Installation',
                    'description' => 'Mounting, securing, and wiring indoor ceiling lights, spotlights, and chandeliers.',
                    'base_price' => 180.00,
                    'estimated_duration_minutes' => 45,
                ],
                [
                    'name' => 'Wall Socket & Switch Replacement',
                    'description' => 'Upgrade damaged power sockets, switches, and install USB charging outlets.',
                    'base_price' => 120.00,
                    'estimated_duration_minutes' => 30,
                ],
            ],
            'Air Conditioning' => [
                [
                    'name' => 'AC Full Seasonal Maintenance & Cleaning',
                    'description' => 'Filter wash, coil chemical cleansing, drain line flush, and airflow test.',
                    'base_price' => 350.00,
                    'estimated_duration_minutes' => 90,
                ],
                [
                    'name' => 'AC Freon Gas Refill & Pressure Check',
                    'description' => 'Check refrigerant leaks, recharge R410A/R22 gas, and test cooling output.',
                    'base_price' => 450.00,
                    'estimated_duration_minutes' => 60,
                ],
                [
                    'name' => 'AC Compressor & Capacitor Diagnosis',
                    'description' => 'Diagnose humming, non-cooling compressor units, and replace faulty capacitors.',
                    'base_price' => 280.00,
                    'estimated_duration_minutes' => 60,
                ],
            ],
            'Painting' => [
                [
                    'name' => 'Single Room Wall Repainting',
                    'description' => 'Wall preparation, primer coating, and 2-layer high-durability finish paint.',
                    'base_price' => 800.00,
                    'estimated_duration_minutes' => 240,
                ],
                [
                    'name' => 'Wall Crack Patching & Touch-up',
                    'description' => 'Fill cracks, plaster smoothing, sand down imperfections, and color match touch-up.',
                    'base_price' => 250.00,
                    'estimated_duration_minutes' => 90,
                ],
            ],
            'Appliance Repair' => [
                [
                    'name' => 'Washing Machine Repair',
                    'description' => 'Fix spinning issues, water drainage problems, drum noise, and motor belt faults.',
                    'base_price' => 300.00,
                    'estimated_duration_minutes' => 75,
                ],
                [
                    'name' => 'Refrigerator & Freezer Repair',
                    'description' => 'Solve cooling failures, thermostat defects, door seal leakages, and defrost issues.',
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
                Service::firstOrCreate(
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
            }
        }
    }
}
