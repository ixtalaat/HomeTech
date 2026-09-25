<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Seed the operating branches with their home cities.
     *
     * Priorities are editable defaults: the city branch serves its own
     * requests first, higher-priority branches back it up on overflow.
     *
     * @var array<int, array{name: string, priority: int, cities: list<string>}>
     */
    private const BRANCHES = [
        ['name' => 'Riyadh', 'priority' => 50, 'cities' => ['Riyadh']],
        ['name' => 'Jeddah', 'priority' => 40, 'cities' => ['Jeddah']],
        ['name' => 'Dammam', 'priority' => 30, 'cities' => ['Dammam']],
        ['name' => 'Mecca', 'priority' => 20, 'cities' => ['Mecca']],
        ['name' => 'Medina', 'priority' => 10, 'cities' => ['Medina']],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::BRANCHES as $data) {
            $branch = Branch::firstOrCreate(
                ['name' => $data['name']],
                ['priority' => $data['priority'], 'is_active' => true]
            );

            foreach ($data['cities'] as $city) {
                $branch->cities()->firstOrCreate(['name' => $city]);
            }
        }

        $this->seedDemoManager();
    }

    /**
     * Seed one demo branch manager per branch (credentials documented in
     * README; override per deploy via env, like technician passwords).
     */
    private function seedDemoManager(): void
    {
        foreach (self::BRANCHES as $data) {
            $branch = Branch::where('name', $data['name'])->first();

            if ($branch === null || $branch->manager_user_id !== null) {
                continue;
            }

            $manager = User::firstOrCreate(
                ['email' => strtolower($data['name']).'.manager@hometech.test'],
                [
                    'name' => $data['name'].' Manager',
                    'password' => env('BRANCH_MANAGER_PASSWORD', 'password'),
                    'role' => UserRole::Manager,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $branch->update(['manager_user_id' => $manager->id]);
        }
    }
}
