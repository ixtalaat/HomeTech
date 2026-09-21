<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\WorkOrderService;
use Illuminate\Support\Facades\Storage;

it('streams owned photos and hides foreign ones', function () {
    Storage::fake('local');

    $workOrder = inProgressWorkOrder();
    $techUser = $workOrder->technician->user;
    $owner = $workOrder->request->user;
    $stranger = User::factory()->create(['role' => UserRole::Customer]);

    app(WorkOrderService::class)->uploadPhotos(
        $workOrder,
        $techUser,
        'before',
        [fakePngPhoto()]
    );

    $path = $workOrder->refresh()->before_photos[0];

    $this->get(route('files.show', $path))->assertRedirect(route('login'));
    $this->actingAs($techUser)->get(route('files.show', $path))->assertOk();
    $this->actingAs($owner)->get(route('files.show', $path))->assertOk();
    $this->actingAs($stranger)->get(route('files.show', $path))->assertNotFound();
});
