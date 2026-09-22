<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\RequestStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Personal overview for a customer: their active jobs, visits, and balance.
     *
     * @return array<string, mixed>
     */
    public function customerOverview(User $user): array
    {
        $today = now()->format('Y-m-d');

        $activeStatuses = [
            RequestStatus::Approved,
            RequestStatus::TechnicianAssigned,
            RequestStatus::Scheduled,
            RequestStatus::TechnicianOnWay,
            RequestStatus::InProgress,
            RequestStatus::WaitingCustomerApproval,
        ];

        return [
            'active_services' => MaintenanceRequest::ownedBy($user->id)->whereIn('status', $activeStatuses)->count(),
            'upcoming_visits' => Appointment::whereHas('request', fn ($query): Builder => $query->ownedBy($user->id))
                ->where('date', '>=', $today)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->count(),
            'completed_jobs' => MaintenanceRequest::ownedBy($user->id)
                ->whereIn('status', [RequestStatus::Completed, RequestStatus::Invoiced, RequestStatus::Paid, RequestStatus::Closed])
                ->count(),
            'outstanding_balance' => (float) Invoice::ownedBy($user->id)->outstanding()
                ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as total')->value('total'),
            'recent_requests' => MaintenanceRequest::ownedBy($user->id)
                ->with(['service', 'appointment'])
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Personal overview for a technician: assigned work, visits, completions.
     *
     * @return array<string, mixed>
     */
    public function technicianOverview(User $user): array
    {
        $today = now()->format('Y-m-d');
        $technician = $user->technician;

        if ($technician === null) {
            return [
                'active_jobs' => 0,
                'upcoming_visits' => 0,
                'completed_jobs' => 0,
                'upcoming' => collect(),
                'assigned' => collect(),
            ];
        }

        return [
            'active_jobs' => MaintenanceRequest::where('technician_id', $technician->id)
                ->whereIn('status', [RequestStatus::Scheduled, RequestStatus::TechnicianOnWay, RequestStatus::InProgress, RequestStatus::WaitingCustomerApproval])
                ->count(),
            'upcoming_visits' => Appointment::forTechnicianOn($technician->id, $today)->blocking()->count()
                + Appointment::where('technician_id', $technician->id)
                    ->where('date', '>', $today)
                    ->blocking()
                    ->count(),
            'completed_jobs' => WorkOrder::forTechnician($technician->id)
                ->where('status', WorkOrderStatus::Completed)
                ->count(),
            'upcoming' => Appointment::with(['request.service', 'request.address'])
                ->where('technician_id', $technician->id)
                ->where('date', '>=', $today)
                ->blocking()
                ->orderBy('date')
                ->orderBy('start_time')
                ->limit(5)
                ->get(),
            'assigned' => MaintenanceRequest::with(['service', 'appointment'])
                ->where('technician_id', $technician->id)
                ->whereIn('status', [RequestStatus::Scheduled, RequestStatus::TechnicianOnWay])
                ->whereDoesntHave('workOrder')
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Operational overview for the admin dashboard (PRD §25).
     *
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $today = now()->format('Y-m-d');

        return [
            'todays_jobs' => Appointment::where('date', $today)->whereNotIn('status', ['cancelled'])->count(),
            'pending_requests' => MaintenanceRequest::where('status', RequestStatus::PendingReview)->count(),
            'active_jobs' => MaintenanceRequest::whereIn('status', [RequestStatus::InProgress, RequestStatus::WaitingCustomerApproval])->count(),
            'completed_today' => MaintenanceRequest::where('status', RequestStatus::Completed)->whereDate('updated_at', $today)->count(),
            'unpaid_invoices' => Invoice::outstanding()->count(),
            'outstanding_total' => (float) Invoice::outstanding()->selectRaw('COALESCE(SUM(total - paid_amount), 0) as total')->value('total'),
            'low_stock_count' => InventoryItem::lowStock()->count(),
            'low_stock_items' => InventoryItem::lowStock()->with('translations')->orderBy('current_stock')->limit(5)->get(),
            'unmanaged_branches' => Branch::whereNull('manager_user_id')->where('is_active', true)->orderBy('name')->limit(5)->get(['id', 'name']),
            'upcoming_appointments' => Appointment::with(['request.service.translations', 'request.service.category', 'technician.user'])
                ->where('date', '>=', $today)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->orderBy('date')
                ->orderBy('start_time')
                ->limit(8)
                ->get(),
            'unassigned_jobs' => MaintenanceRequest::with(['service.translations', 'service.category', 'user'])
                ->where('status', RequestStatus::Approved)
                ->whereNull('technician_id')
                ->latest()
                ->limit(8)
                ->get(),
            'waiting_approval' => MaintenanceRequest::with(['service.translations', 'service.category', 'user'])
                ->where('status', RequestStatus::WaitingCustomerApproval)
                ->latest()
                ->limit(8)
                ->get(),
            'recent_requests' => MaintenanceRequest::with(['service.translations', 'service.category', 'user'])
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * Daily revenue for the last 30 days (from payments).
     *
     * @return Collection<int, object>
     */
    public function revenueDaily(): Collection
    {
        return Payment::selectRaw('DATE(paid_at) as day, COALESCE(SUM(amount), 0) as total')
            ->where('paid_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    /**
     * Monthly revenue for the last 12 months (from payments).
     *
     * @return Collection<int, object>
     */
    public function revenueMonthly(): Collection
    {
        return Payment::selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, COALESCE(SUM(amount), 0) as total")
            ->where('paid_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Revenue by service (invoiced line totals).
     *
     * @return Collection<int, object>
     */
    public function revenueByService(): Collection
    {
        $rows = InvoiceItem::selectRaw('services.id as service_id, services.name as name, COALESCE(SUM(invoice_items.total), 0) as total')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('maintenance_requests', 'maintenance_requests.id', '=', 'invoices.maintenance_request_id')
            ->join('services', 'services.id', '=', 'maintenance_requests.service_id')
            ->whereNotIn('invoices.status', [InvoiceStatus::Cancelled])
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total')
            ->get();

        $services = Service::with('translations')->whereIn('id', $rows->pluck('service_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($services) {
            $row->name = $services->get($row->service_id)?->display_name ?? $row->name;

            return $row;
        });
    }

    /**
     * Revenue by technician (paid amounts on their requests' invoices).
     *
     * @return Collection<int, object>
     */
    public function revenueByTechnician(): Collection
    {
        return Invoice::selectRaw('users.name as name, COALESCE(SUM(invoices.paid_amount), 0) as total')
            ->join('maintenance_requests', 'maintenance_requests.id', '=', 'invoices.maintenance_request_id')
            ->join('technicians', 'technicians.id', '=', 'maintenance_requests.technician_id')
            ->join('users', 'users.id', '=', 'technicians.user_id')
            ->whereNotIn('invoices.status', [InvoiceStatus::Cancelled])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Outstanding invoices with balances.
     *
     * @return Collection<int, Invoice>
     */
    public function outstandingInvoices(): Collection
    {
        return Invoice::outstanding()->with('user')->latest()->get();
    }

    /**
     * Job counts by request status.
     *
     * @return array<string, int>
     */
    public function jobsByStatus(): array
    {
        return MaintenanceRequest::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * Job counts by service.
     *
     * @return Collection<int, object>
     */
    public function jobsByService(): Collection
    {
        $rows = MaintenanceRequest::selectRaw('services.id as service_id, services.name as name, COUNT(*) as total')
            ->join('services', 'services.id', '=', 'maintenance_requests.service_id')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total')
            ->get();

        $services = Service::with('translations')->whereIn('id', $rows->pluck('service_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($services) {
            $row->name = $services->get($row->service_id)?->display_name ?? $row->name;

            return $row;
        });
    }

    /**
     * Average completion time in hours (visit start to work completion).
     */
    public function averageCompletionHours(): ?float
    {
        $seconds = WorkOrder::whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_seconds')
            ->value('avg_seconds');

        return $seconds === null ? null : round((float) $seconds / 3600, 1);
    }

    /**
     * Per-technician performance: workload, completions, revenue, ratings.
     *
     * @return Collection<int, object>
     */
    public function technicianStats(): Collection
    {
        return Technician::selectRaw(implode(', ', [
            'technicians.id as id',
            'users.name as name',
            'COUNT(DISTINCT maintenance_requests.id) as assigned',
            "COUNT(DISTINCT CASE WHEN maintenance_requests.status = 'completed' THEN maintenance_requests.id END) as completed",
            "COUNT(DISTINCT CASE WHEN maintenance_requests.status = 'cancelled' THEN maintenance_requests.id END) as cancelled",
            'COALESCE(SUM(invoices.paid_amount), 0) as revenue',
            'COALESCE(AVG(reviews.rating), 0) as avg_rating',
            'COUNT(DISTINCT reviews.id) as reviews_count',
        ]))
            ->join('users', 'users.id', '=', 'technicians.user_id')
            ->leftJoin('maintenance_requests', 'maintenance_requests.technician_id', '=', 'technicians.id')
            ->leftJoin('invoices', function ($join): void {
                $join->on('invoices.maintenance_request_id', '=', 'maintenance_requests.id')
                    ->whereNotIn('invoices.status', [InvoiceStatus::Cancelled]);
            })
            ->leftJoin('reviews', 'reviews.maintenance_request_id', '=', 'maintenance_requests.id')
            ->groupBy('technicians.id', 'users.name')
            ->orderBy('name')
            ->get();
    }

    /**
     * Inventory overview: stock, consumption, low stock, most used, movements.
     *
     * @return array<string, mixed>
     */
    public function inventoryReport(): array
    {
        return [
            'items' => InventoryItem::with('translations')->orderBy('name')->get(),
            'low_stock' => InventoryItem::with('translations')->lowStock()->orderBy('current_stock')->get(),
            'most_used' => $this->mostUsedMaterials(),
            'recent_movements' => InventoryMovement::with(['item.translations'])->latest()->limit(20)->get(),
        ];
    }

    /**
     * Most-consumed materials with translated display names.
     *
     * @return Collection<int, object>
     */
    private function mostUsedMaterials(): Collection
    {
        $rows = InventoryMovement::selectRaw('inventory_items.id as item_id, inventory_items.name as name, COALESCE(SUM(ABS(inventory_movements.quantity)), 0) as moved')
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_movements.inventory_item_id')
            ->where('inventory_movements.type', 'consumption')
            ->groupBy('inventory_items.id', 'inventory_items.name')
            ->orderByDesc('moved')
            ->limit(10)
            ->get();

        $items = InventoryItem::with('translations')->whereIn('id', $rows->pluck('item_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($items) {
            $row->name = $items->get($row->item_id)?->display_name ?? $row->name;

            return $row;
        });
    }

    /**
     * Dispatcher week view: active technicians with their bookings per day.
     *
     * @return array{days: Collection<int, Carbon>, technicians: Collection<int, Technician>, bookings: Collection<int, Collection<string, Collection<int, Appointment>>>, branches: Collection<int, Branch>, branchId: ?int, weekOffset: int}
     */
    public function calendarWeek(?string $week = null, ?int $branchId = null): array
    {
        $offset = max(-4, min(8, (int) ($week ?? 0)));
        $monday = now()->startOfWeek(1)->addWeeks($offset)->startOfDay();

        $days = collect(range(0, 6))->map(fn (int $index) => $monday->copy()->addDays($index));

        $technicians = Technician::active()
            ->with('user')
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('id')
            ->get();

        $bookings = Appointment::with(['request.service'])
            ->whereIn('technician_id', $technicians->pluck('id'))
            ->whereBetween('date', [$days->first()->format('Y-m-d'), $days->last()->format('Y-m-d')])
            ->where('status', '!=', AppointmentStatus::Cancelled)
            ->orderBy('start_time')
            ->get()
            ->groupBy([
                fn (Appointment $appointment): int => $appointment->technician_id,
                fn (Appointment $appointment): string => $appointment->date->format('Y-m-d'),
            ]);

        return [
            'days' => $days,
            'technicians' => $technicians,
            'bookings' => $bookings,
            'branches' => Branch::where('is_active', true)->orderByDesc('priority')->orderBy('name')->get(),
            'branchId' => $branchId,
            'weekOffset' => $offset,
        ];
    }

    /**
     * Total revenue ever collected (dashboard helper, avoids DB::raw in views).
     */
    public function totalRevenue(): float
    {
        return (float) (Payment::sum('amount') ?? 0);
    }
}
