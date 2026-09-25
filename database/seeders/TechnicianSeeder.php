<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TechnicianSeeder extends Seeder
{
    /**
     * Demo technicians with their skills (category slugs).
     *
     * @var array<int, array{name: string, email: string, phone: string, branch: string, skills: array<int, string>}>
     */
    private const TECHNICIANS = [
        [
            'name' => 'Ahmed Hassan',
            'email' => 'ahmed@hometech.com',
            'phone' => '0550000001',
            'branch' => 'Riyadh',
            'skills' => ['plumbing'],
        ],
        [
            'name' => 'Sara Mahmoud',
            'email' => 'sara@hometech.com',
            'phone' => '0550000002',
            'branch' => 'Riyadh',
            'skills' => ['electrical', 'air-conditioning'],
        ],
        [
            'name' => 'Omar Khaled',
            'email' => 'omar@hometech.com',
            'phone' => '0550000003',
            'branch' => 'Jeddah',
            'skills' => ['painting', 'appliance-repair'],
        ],
        [
            'name' => 'Khalid Al-Otaibi',
            'email' => 'khalid@hometech.com',
            'phone' => '0550000004',
            'branch' => 'Jeddah',
            'skills' => ['air-conditioning', 'appliance-repair'],
        ],
        [
            'name' => 'Reem Al-Qahtani',
            'email' => 'reem@hometech.com',
            'phone' => '0550000005',
            'branch' => 'Dammam',
            'skills' => ['plumbing', 'electrical'],
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
                    // Demo credential (documented in README); override per deploy via env.
                    'password' => $password = env('TECHNICIAN_PASSWORD', 'password'),
                    'phone' => $data['phone'],
                    'role' => UserRole::Technician,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            if (! isset($password)) {
                $this->command->info("Technician {$data['email']} already exists; password unchanged.");
            }

            unset($password);

            $technician = $user->technician()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $data['phone'],
                    'branch_id' => Branch::where('name', $data['branch'])->first()?->id,
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
