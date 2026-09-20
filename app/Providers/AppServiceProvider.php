<?php

namespace App\Providers;

use App\Models\InventoryItem;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use App\Policies\CustomerPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\MaintenanceRequestPolicy;
use App\Policies\TechnicianPolicy;
use App\Policies\WorkOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, CustomerPolicy::class);
        Gate::policy(MaintenanceRequest::class, MaintenanceRequestPolicy::class);
        Gate::policy(Technician::class, TechnicianPolicy::class);
        Gate::policy(WorkOrder::class, WorkOrderPolicy::class);
        Gate::policy(InventoryItem::class, InventoryPolicy::class);
    }
}
