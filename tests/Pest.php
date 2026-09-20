<?php

use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Services\TechnicianAssignmentService;
use App\Services\WorkOrderService;
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
