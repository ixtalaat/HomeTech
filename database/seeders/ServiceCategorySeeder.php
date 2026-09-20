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
                'description' => 'Comprehensive plumbing installations, leak fixes, heater repairs, and drain clearing.',
                'icon' => 'wrench',
            ],
            [
                'name' => 'Electrical',
                'description' => 'Expert electrical diagnostics, circuit troubleshooting, rewiring, and fixture setup.',
                'icon' => 'zap',
            ],
            [
                'name' => 'Air Conditioning',
                'description' => 'AC unit maintenance, gas refilling, filter deep cleaning, and cooling optimization.',
                'icon' => 'snowflake',
            ],
            [
                'name' => 'Painting',
                'description' => 'Interior and exterior wall painting, plaster repairs, and waterproofing coatings.',
                'icon' => 'paint-brush',
            ],
            [
                'name' => 'Appliance Repair',
                'description' => 'Repair services for washing machines, refrigerators, dishwashers, and ovens.',
                'icon' => 'cpu',
            ],
        ];

        foreach ($categories as $cat) {
            ServiceCategory::firstOrCreate(
                ['name' => $cat['name']],
                [
                    'slug' => Str::slug($cat['name']),
                    'description' => $cat['description'],
                    'icon' => $cat['icon'],
                    'is_active' => true,
                ]
            );
        }
    }
}
