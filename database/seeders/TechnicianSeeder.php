<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TechnicianSeeder extends Seeder
{
    /**
     * Demo technicians with their skills (category slugs).
     *
     * @var array<int, array{name: string, email: string, phone: string, skills: array<int, string>}>
     */
    private const TECHNICIANS = [
        [
            'name' => 'Ahmed Hassan',
            'email' => 'ahmed@hometech.com',
            'phone' => '01000000001',
            'skills' => ['plumbing'],
        ],
        [
            'name' => 'Sara Mahmoud',
            'email' => 'sara@hometech.com',
            'phone' => '01000000002',
            'skills' => ['electrical', 'air-conditioning'],
        ],
        [
            'name' => 'Omar Khaled',
            'email' => 'omar@hometech.com',
            'phone' => '01000000003',
            'skills' => ['painting', 'appliance-repair'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ServiceCategory::pluck('id', 'slug');

        foreach (self::TECHNICIANS as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password = Str::random(12),
                    'phone' => $data['phone'],
                    'role' => UserRole::Technician,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            if (! isset($password)) {
                $this->command->info("Technician {$data['email']} already exists; password unchanged.");
            } else {
                $this->command->info("Technician {$data['email']} created with password: {$password}");
            }

            unset($password);

            $technician = $user->technician()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $data['phone'],
                    'is_active' => true,
                    'hired_at' => now(),
                ]
            );

            $skillIds = collect($data['skills'])
                ->map(fn (string $slug): ?int => $categories->get($slug))
                ->filter()
                ->values()
                ->all();

            $technician->categories()->sync($skillIds);
        }
    }
}
