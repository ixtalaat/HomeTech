<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Seed finished jobs with top-rated reviews so the home page
     * testimonials strip has real content to show.
     */
    public function run(): void
    {
        if (app(ReviewService::class)->spotlight()->count() >= 3) {
            return;
        }

        $showcase = [
            [
                'name' => 'Layla Hassan',
                'email' => 'layla.demo@hometech.com',
                'rating' => 5,
                'comment' => 'Technician arrived on time and fixed everything in one visit. Spotless work!',
            ],
            [
                'name' => 'Omar Farouk',
                'email' => 'omar.demo@hometech.com',
                'rating' => 5,
                'comment' => 'الفني كان محترم وشغله نضيف، والسعر زي ما اتفقنا بالظبط.',
            ],
            [
                'name' => 'Mariam Samir',
                'email' => 'mariam.demo@hometech.com',
                'rating' => 4,
                'comment' => 'Great service overall. Booking took a minute, but the repair itself was perfect.',
            ],
        ];

        $services = Service::where('is_active', true)->orderBy('id')->limit(count($showcase))->get();

        foreach ($showcase as $index => $entry) {
            $service = $services->get($index);

            if ($service === null) {
                continue;
            }

            $customer = User::firstOrCreate(
                ['email' => $entry['email']],
                [
                    'name' => $entry['name'],
                    'password' => 'password',
                    'phone' => '0100000001'.$index,
                    'role' => UserRole::Customer,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $address = $customer->addresses()->firstOrCreate(
                ['title' => 'Home'],
                [
                    'street' => 'Demo Street '.($index + 1),
                    'city' => 'Cairo',
                    'is_default' => true,
                ]
            );

            $request = MaintenanceRequest::firstOrCreate(
                ['user_id' => $customer->id, 'service_id' => $service->id],
                [
                    'address_id' => $address->id,
                    'description' => 'Demo finished job.',
                    'preferred_date' => now()->subDays(10 + $index)->format('Y-m-d'),
                    'preferred_time' => '10:00',
                    'status' => RequestStatus::Completed,
                ]
            );

            if ($request->wasRecentlyCreated) {
                $request->statusHistories()->create([
                    'from_status' => null,
                    'status' => RequestStatus::Completed->value,
                    'changed_by' => $customer->id,
                ]);
            }

            if ($request->review === null) {
                app(ReviewService::class)->submit($request->refresh(), $customer, $entry['rating'], $entry['comment']);
            }
        }
    }
}
