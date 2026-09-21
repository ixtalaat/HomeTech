<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\InventoryItem;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Seed showcase data: stocked materials, a demo customer with an
     * address, and one pending request for admins to review.
     */
    public function run(): void
    {
        $inventory = app(InventoryService::class);
        $manager = User::where('role', UserRole::Manager)->first()
            ?? User::where('role', UserRole::Admin)->first();

        foreach ($this->materials() as $material) {
            $item = InventoryItem::firstOrCreate(
                ['name' => $material['name']],
                [
                    'sku' => $material['sku'],
                    'unit' => 'pcs',
                    'current_stock' => 0,
                    'low_stock_threshold' => 5,
                    'unit_cost' => $material['cost'],
                ]
            );

            if ($item->current_stock < $material['stock']) {
                $inventory->purchase($item->refresh(), $material['stock'] - $item->current_stock, $manager, 'Demo stock.');
            }
        }

        $customer = User::firstOrCreate(
            ['email' => 'demo@hometech.com'],
            [
                'name' => 'Demo Customer',
                'password' => env('DEMO_CUSTOMER_PASSWORD', 'password'),
                'phone' => '01000000009',
                'role' => UserRole::Customer,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $address = $customer->addresses()->firstOrCreate(
            ['title' => 'Home'],
            [
                'street' => '12 Nile Street, Apt 4',
                'city' => 'Cairo',
                'notes' => 'Near the metro station.',
                'is_default' => true,
            ]
        );

        $service = Service::where('slug', 'ac-compressor-capacitor-diagnosis')->first()
            ?? Service::first();

        if ($service !== null && ! MaintenanceRequest::where('user_id', $customer->id)->exists()) {
            MaintenanceRequest::create([
                'user_id' => $customer->id,
                'service_id' => $service->id,
                'address_id' => $address->id,
                'description' => 'My AC is running but is not cooling the room.',
                'preferred_date' => now()->addDays(3)->format('Y-m-d'),
                'preferred_time' => '10:00',
                'status' => RequestStatus::PendingReview,
            ])->statusHistories()->create([
                'from_status' => null,
                'status' => RequestStatus::PendingReview->value,
                'changed_by' => $customer->id,
            ]);
        }
    }

    /**
     * Showcase materials with opening stock levels.
     *
     * @return array<int, array{name: string, sku: string, cost: float, stock: int}>
     */
    private function materials(): array
    {
        return [
            ['name' => 'Capacitor', 'sku' => 'SKU-CAP', 'cost' => 150.00, 'stock' => 25],
            ['name' => 'Copper Connector', 'sku' => 'SKU-COP', 'cost' => 25.00, 'stock' => 100],
            ['name' => 'Water Pipe (1m)', 'sku' => 'SKU-PIP', 'cost' => 80.00, 'stock' => 40],
            ['name' => 'Faucet Cartridge', 'sku' => 'SKU-FAU', 'cost' => 120.00, 'stock' => 3],
            ['name' => 'LED Panel', 'sku' => 'SKU-LED', 'cost' => 200.00, 'stock' => 15],
        ];
    }
}
