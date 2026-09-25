<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\Technician;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\RequestReviewService;
use App\Services\TechnicianAssignmentService;
use App\Services\WorkOrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Seed showcase data: stocked materials, a demo customer with an
     * address, and one pending request for admins to review.
     */
    public function run(): void
    {
        $this->backfillBranchesAndSchedules();

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

            $item->saveTranslations(['ar' => ['name' => $material['name_ar']]]);

            if ($item->current_stock < $material['stock']) {
                $inventory->purchase($item->refresh(), $material['stock'] - $item->current_stock, $manager, 'Demo stock.');
            }
        }

        $customer = User::firstOrCreate(
            ['email' => 'demo@hometech.com'],
            [
                'name' => 'Abdullah Al-Rashid',
                'password' => env('DEMO_CUSTOMER_PASSWORD', 'password'),
                'phone' => '0550000009',
                'role' => UserRole::Customer,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $address = $customer->addresses()->firstOrCreate(
            ['title' => 'Home'],
            [
                'street' => '12 King Fahd Road, Apt 4',
                'city' => 'Riyadh',
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

        $this->stageShowcaseJob($customer, $address);
    }

    /**
     * Link legacy demo technicians to the Riyadh branch and give every
     * technician without one the default weekly schedule, so automatic
     * assignment has coverage on reseeded environments.
     */
    private function backfillBranchesAndSchedules(): void
    {
        $branch = Branch::firstOrCreate(
            ['name' => 'Riyadh'],
            ['priority' => 50, 'is_active' => true]
        );

        foreach (Technician::whereNull('branch_id')->get() as $technician) {
            $technician->update(['branch_id' => $branch->id]);
        }

        foreach (Technician::whereDoesntHave('schedules')->get() as $technician) {
            $technician->schedules()->createMany(TechnicianSchedule::defaultWeek());
        }
    }

    /**
     * Stage two lively jobs: one in-progress visit with today's appointment
     * (active jobs, today's jobs, technician portal) and one completed job
     * with an issued unpaid invoice (unpaid invoices, revenue reports).
     *
     * Each stages only when no equivalent exists, so reseeds stay quiet.
     */
    private function stageShowcaseJob(User $customer, Address $address): void
    {
        $admin = User::where('role', UserRole::Admin)->first();
        $plumber = Technician::whereHas('user', fn ($query): Builder => $query->where('email', 'ahmed@hometech.com'))->first();
        $electrician = Technician::whereHas('user', fn ($query): Builder => $query->where('email', 'sara@hometech.com'))->first();

        if ($admin === null || $plumber === null || $electrician === null) {
            return;
        }

        $this->stageActiveJob($customer, $address, $admin, $plumber);
        $this->stageCompletedJob($customer, $address, $admin, $electrician);
    }

    /**
     * Stage an in-progress visit with today's appointment.
     */
    private function stageActiveJob(User $customer, Address $address, User $admin, Technician $plumber): void
    {
        $hasActiveJob = MaintenanceRequest::where('user_id', $customer->id)
            ->whereIn('status', [
                RequestStatus::Approved,
                RequestStatus::TechnicianAssigned,
                RequestStatus::Scheduled,
                RequestStatus::TechnicianOnWay,
                RequestStatus::InProgress,
                RequestStatus::WaitingCustomerApproval,
            ])
            ->exists();

        if ($hasActiveJob) {
            return;
        }

        $service = Service::where('slug', 'faucet-tap-repair')->first() ?? Service::first();

        $request = MaintenanceRequest::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'address_id' => $address->id,
            'description' => 'Kitchen faucet is leaking constantly.',
            'preferred_date' => now()->addDays(2)->format('Y-m-d'),
            'preferred_time' => '09:00',
            'status' => RequestStatus::PendingReview,
        ]);

        app(RequestReviewService::class)->approve($request->refresh(), $admin, []);
        app(TechnicianAssignmentService::class)->assign($request->refresh(), $plumber, $admin);

        $request->refresh()->appointment->update(['date' => now()->format('Y-m-d')]);

        $workOrders = app(WorkOrderService::class);
        $workOrder = $workOrders->startVisit($request->refresh(), $plumber->user);
        $workOrders->recordDiagnosis($workOrder, $plumber->user, 'Worn faucet cartridge, steady drip.');
        $workOrders->recordNotes($workOrder, $plumber->user, 'Cartridge replaced, pressure tested, no further leaks.');
    }

    /**
     * Stage a completed job with an issued unpaid invoice.
     */
    private function stageCompletedJob(User $customer, Address $address, User $admin, Technician $electrician): void
    {
        $hasFinishedJob = MaintenanceRequest::where('user_id', $customer->id)
            ->whereIn('status', [
                RequestStatus::Completed,
                RequestStatus::Invoiced,
                RequestStatus::Paid,
                RequestStatus::Closed,
            ])
            ->exists();

        if ($hasFinishedJob) {
            return;
        }

        $service = Service::where('slug', 'wall-socket-switch-replacement')->first() ?? Service::first();

        $request = MaintenanceRequest::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'address_id' => $address->id,
            'description' => 'Living room socket sparks when plugging in.',
            'preferred_date' => now()->addDays(2)->format('Y-m-d'),
            'preferred_time' => '14:00',
            'status' => RequestStatus::PendingReview,
        ]);

        $workOrders = app(WorkOrderService::class);
        app(RequestReviewService::class)->approve($request->refresh(), $admin, []);
        app(TechnicianAssignmentService::class)->assign($request->refresh(), $electrician, $admin);

        $workOrder = $workOrders->startVisit($request->refresh(), $electrician->user);
        $workOrders->recordDiagnosis($workOrder, $electrician->user, 'Loose wiring in the socket box.');
        $workOrders->recordNotes($workOrder, $electrician->user, 'Rewired and replaced the socket face.');
        $workOrders->addLaborItem($workOrder->refresh(), $electrician->user, 'Socket rewiring', 120.00);
        $workOrders->complete($workOrder->refresh(), $electrician->user);

        $invoices = app(InvoiceService::class);
        $invoice = $invoices->generate($workOrder->refresh(), $admin);
        $invoices->issue($invoice, $admin);
    }

    /**
     * Showcase materials with opening stock levels.
     *
     * @return array<int, array{name: string, name_ar: string, sku: string, cost: float, stock: int}>
     */
    private function materials(): array
    {
        return [
            ['name' => 'Capacitor', 'name_ar' => 'مكثف', 'sku' => 'SKU-CAP', 'cost' => 45.00, 'stock' => 25],
            ['name' => 'Copper Connector', 'name_ar' => 'وصلة نحاس', 'sku' => 'SKU-COP', 'cost' => 15.00, 'stock' => 100],
            ['name' => 'Water Pipe (1m)', 'name_ar' => 'ماسورة مياه (1م)', 'sku' => 'SKU-PIP', 'cost' => 35.00, 'stock' => 40],
            ['name' => 'Faucet Cartridge', 'name_ar' => 'قلب خلاط', 'sku' => 'SKU-FAU', 'cost' => 60.00, 'stock' => 3],
            ['name' => 'LED Panel', 'name_ar' => 'لوح ليد', 'sku' => 'SKU-LED', 'cost' => 85.00, 'stock' => 15],
        ];
    }
}
