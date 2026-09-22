<?php

namespace App\Providers;

use App\Listeners\ForwardDatabaseNotificationsToFcm;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use App\Policies\CustomerPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\MaintenanceRequestPolicy;
use App\Policies\TechnicianPolicy;
use App\Policies\WorkOrderPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Our listeners are registered explicitly in boot(): disable the
        // framework's listener auto-discovery so each one fires exactly once.
        EventServiceProvider::disableEventDiscovery();
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
        Gate::policy(Invoice::class, InvoicePolicy::class);

        // Mirror every in-app notification to Firebase push (best effort).
        Event::listen(NotificationSent::class, ForwardDatabaseNotificationsToFcm::class);

        // Brute-force protection keyed per account (plus IP), so one
        // attacker's attempts never lock out other users — or other tests.
        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->input('email', '').'|'.$request->ip());
        });

        RateLimiter::for('password', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->input('email', '').'|'.$request->ip());
        });
    }
}
