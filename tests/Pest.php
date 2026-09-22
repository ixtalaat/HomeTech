<?php

use App\Models\Address;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Services\AdditionalWorkService;
use App\Services\TechnicianAssignmentService;
use App\Services\WorkOrderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a technician skilled for the given service's category.
 */
function skilledTechnician(Service $service): Technician
{
    $technician = Technician::factory()->create();
    $technician->categories()->sync([$service->service_category_id]);

    return $technician->refresh();
}

/**
 * Create a real 1x1 PNG upload without requiring the GD extension.
 */
function fakePngPhoto(string $name = 'ac.png'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'photo').'.png';
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

    return new UploadedFile($path, $name, 'image/png', null, true);
}

/**
 * Create an approved request, assign a skilled technician, and book the slot.
 *
 * The booked visit is 09:00–11:00 on the preferred date.
 */
function scheduledRequest(): MaintenanceRequest
{
    $request = MaintenanceRequest::factory()->approved()->create([
        'preferred_time' => '09:00',
    ]);
    $request->service->update(['estimated_duration_minutes' => 120]);
    $technician = skilledTechnician($request->service);

    return app(TechnicianAssignmentService::class)->assign($request->refresh(), $technician);
}

/**
 * Create a scheduled request and start the visit, returning the work order.
 */
function inProgressWorkOrder(): WorkOrder
{
    $request = scheduledRequest();

    return app(WorkOrderService::class)->startVisit($request->refresh(), $request->technician->user);
}

/**
 * Create a completed work order with labor, materials, and one approved extra.
 */
function completedWorkOrderWithCharges(): WorkOrder
{
    $workOrder = inProgressWorkOrder();
    $services = app(WorkOrderService::class);
    $techUser = $workOrder->technician->user;

    $services->recordDiagnosis($workOrder, $techUser, 'Faulty capacitor.');
    $services->recordNotes($workOrder, $techUser, 'Replaced and tested.');
    $services->addLaborItem($workOrder, $techUser, 'AC Diagnosis', 100.00);

    $item = InventoryItem::factory()->create(['current_stock' => 10, 'unit_cost' => 150.00]);
    $services->recordMaterialUsage($workOrder->refresh(), $techUser, $item, 1);

    $extras = app(AdditionalWorkService::class);
    $extra = $extras->request($workOrder->refresh(), $techUser, 'Replace connector.', 100.00);
    $extras->decide($extra, $workOrder->request->user, true);

    return $services->complete($workOrder->refresh(), $techUser);
}

/**
 * Create a branch serving one city.
 */
function staffedBranch(string $city = 'Riyadh', int $priority = 10): Branch
{
    $branch = Branch::factory()->create(['name' => $city.' Branch', 'priority' => $priority]);
    $branch->cities()->create(['name' => $city]);

    return $branch;
}

/**
 * Create a skilled technician in the branch with a full-week schedule.
 */
function workingTechnician(Service $service, Branch $branch): Technician
{
    $technician = Technician::factory()->create(['branch_id' => $branch->id]);
    $technician->categories()->sync([$service->service_category_id]);

    aroundTheClock($technician->refresh());

    return $technician->refresh();
}

/**
 * Give the technician a 00:00–23:59 schedule every day.
 */
function aroundTheClock(Technician $technician): void
{
    $technician->schedules()->delete();

    foreach (range(0, 6) as $day) {
        $technician->schedules()->create([
            'day_of_week' => $day,
            'is_working' => true,
            'start_time' => '00:00',
            'end_time' => '23:59',
        ]);
    }
}

/**
 * Create an approved request for the service in the city on next Monday 10:00.
 */
function mondayRequest(Service $service, string $city = 'Riyadh'): MaintenanceRequest
{
    $date = Carbon::parse('next monday')->format('Y-m-d');

    return MaintenanceRequest::factory()->approved()->create([
        'service_id' => $service->id,
        'address_id' => Address::factory()->create(['city' => $city])->id,
        'preferred_date' => $date,
        'preferred_time' => '10:00',
    ]);
}

/**
 * Create a service with a fixed one-hour duration.
 */
function hourlyService(): Service
{
    return Service::factory()->create(['estimated_duration_minutes' => 60]);
}
