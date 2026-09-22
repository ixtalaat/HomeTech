<?php

use App\Http\Controllers\AdditionalWorkController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BranchManagerController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DiscountApprovalController;
use App\Http\Controllers\Admin\InventoryItemController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\MaintenanceRequestController as AdminMaintenanceRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\TechnicianController;
use App\Http\Controllers\Admin\TechnicianScheduleController;
use App\Http\Controllers\Admin\WorkOrderController as AdminWorkOrderController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Branch\RequestController as BranchRequestController;
use App\Http\Controllers\Branch\TechnicianController as BranchTechnicianController;
use App\Http\Controllers\Branch\TechnicianScheduleController as BranchScheduleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PhoneVerificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushTokenController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceBrowseController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\Technician\WorkOrderController as TechnicianWorkOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

// Public service catalogue
Route::get('/services', [ServiceBrowseController::class, 'index'])->name('services.index');
Route::get('/services/{slug}', [ServiceBrowseController::class, 'show'])->name('services.show');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Public Firebase web-push configuration (client identifiers only, no secrets).
Route::get('/firebase-config', [PushTokenController::class, 'config'])->name('firebase.config');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:auth')->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:auth')->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:password')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:password')->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:10,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:10,1')->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::resource('addresses', AddressController::class)->except(['show']);
    Route::patch('addresses/{address}/default', [AddressController::class, 'setDefault'])->name('addresses.set-default');

    Route::resource('requests', MaintenanceRequestController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['requests' => 'maintenanceRequest']);

    Route::patch('additional-work/{additionalWork}/approve', [AdditionalWorkController::class, 'approve'])->name('additional-work.approve');
    Route::patch('additional-work/{additionalWork}/reject', [AdditionalWorkController::class, 'reject'])->name('additional-work.reject');

    Route::get('invoices/stripe/success', [InvoiceController::class, 'stripeSuccess'])->name('invoices.stripe.success');
    Route::get('invoices/{invoice}/stripe/cancel', [InvoiceController::class, 'stripeCancel'])->name('invoices.stripe.cancel');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::post('invoices/{invoice}/stripe/checkout', [InvoiceController::class, 'stripeCheckout'])->name('invoices.stripe.checkout');

    Route::post('requests/{maintenanceRequest}/cancel', [MaintenanceRequestController::class, 'cancel'])->name('requests.cancel');

    Route::post('requests/{maintenanceRequest}/reviews', [ReviewController::class, 'store'])->name('requests.reviews.store');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

    Route::get('support', [SupportController::class, 'create'])->name('support.create');
    Route::post('support', [SupportController::class, 'store'])->middleware('throttle:10,1')->name('support.store');

    Route::post('push-tokens', [PushTokenController::class, 'store'])->middleware('throttle:30,1')->name('push-tokens.store');
    Route::delete('push-tokens', [PushTokenController::class, 'destroy'])->name('push-tokens.destroy');

    Route::post('phone/send-code', [PhoneVerificationController::class, 'send'])->name('phone.send-code');
    Route::post('phone/verify', [PhoneVerificationController::class, 'verify'])->name('phone.verify');

    Route::get('files/{path}', [FileController::class, 'show'])->where('path', '.*')->name('files.show');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'verified', 'role:technician'])
    ->prefix('technician')->name('technician.')
    ->group(function (): void {
        Route::get('jobs', [TechnicianWorkOrderController::class, 'index'])->name('jobs.index');
        Route::get('jobs/{workOrder}', [TechnicianWorkOrderController::class, 'show'])->name('jobs.show');
        Route::post('jobs/requests/{maintenanceRequest}/start', [TechnicianWorkOrderController::class, 'start'])->name('jobs.start');
        Route::post('jobs/requests/{maintenanceRequest}/on-way', [TechnicianWorkOrderController::class, 'onWay'])->name('jobs.on-way');
        Route::patch('jobs/{workOrder}/diagnosis', [TechnicianWorkOrderController::class, 'recordDiagnosis'])->name('jobs.diagnosis');
        Route::patch('jobs/{workOrder}/notes', [TechnicianWorkOrderController::class, 'recordNotes'])->name('jobs.notes');
        Route::post('jobs/{workOrder}/labor', [TechnicianWorkOrderController::class, 'addLabor'])->name('jobs.labor');
        Route::post('jobs/{workOrder}/materials', [TechnicianWorkOrderController::class, 'recordMaterial'])->name('jobs.materials');
        Route::post('jobs/{workOrder}/additional-work', [TechnicianWorkOrderController::class, 'requestAdditional'])->name('jobs.additional-work');
        Route::patch('jobs/additional-work/{additionalWork}/complete', [TechnicianWorkOrderController::class, 'completeAdditional'])->name('jobs.additional-work.complete');
        Route::post('jobs/{workOrder}/photos', [TechnicianWorkOrderController::class, 'uploadPhotos'])->name('jobs.photos');
        Route::patch('jobs/{workOrder}/complete', [TechnicianWorkOrderController::class, 'complete'])->name('jobs.complete');
    });

Route::middleware(['auth', 'verified', 'role:manager'])
    ->prefix('branch')
    ->name('branch.')
    ->group(function (): void {
        Route::get('/', [BranchDashboardController::class, 'index'])->name('dashboard');

        Route::get('technicians', [BranchTechnicianController::class, 'index'])->name('technicians.index');
        Route::get('technicians/{technician}', [BranchTechnicianController::class, 'show'])->name('technicians.show');
        Route::get('technicians/{technician}/schedule/edit', [BranchScheduleController::class, 'edit'])->name('technicians.schedule.edit');
        Route::put('technicians/{technician}/schedule', [BranchScheduleController::class, 'update'])->name('technicians.schedule.update');

        Route::get('requests', [BranchRequestController::class, 'index'])->name('requests.index');
        Route::get('requests/{maintenanceRequest}', [BranchRequestController::class, 'show'])->name('requests.show');
        Route::patch('requests/{maintenanceRequest}/approve', [BranchRequestController::class, 'approve'])->name('requests.approve');
        Route::patch('requests/{maintenanceRequest}/reject', [BranchRequestController::class, 'reject'])->name('requests.reject');
        Route::patch('requests/{maintenanceRequest}/request-info', [BranchRequestController::class, 'requestInfo'])->name('requests.request-info');
        Route::patch('requests/{maintenanceRequest}/assign', [BranchRequestController::class, 'assign'])->name('requests.assign');
        Route::patch('requests/{maintenanceRequest}/unassign', [BranchRequestController::class, 'unassign'])->name('requests.unassign');
    });

Route::middleware(['auth', 'verified', 'role:admin,manager', 'branch.scope'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
        Route::get('reports/calendar', [ReportController::class, 'calendar'])->name('reports.calendar');
        Route::get('reports/jobs', [ReportController::class, 'jobs'])->name('reports.jobs');
        Route::get('reports/technicians', [ReportController::class, 'technicians'])->name('reports.technicians');
        Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');

        Route::resource('categories', ServiceCategoryController::class);
        Route::resource('branches', BranchController::class)->except(['show']);
        Route::get('managers', [BranchManagerController::class, 'index'])->name('managers.index');
        Route::get('managers/{manager}/edit', [BranchManagerController::class, 'edit'])->name('managers.edit');
        Route::put('managers/{manager}', [BranchManagerController::class, 'update'])->name('managers.update');
        Route::patch('managers/{manager}/toggle-status', [BranchManagerController::class, 'toggleStatus'])->name('managers.toggle-status');
        Route::patch('managers/{manager}/unassign', [BranchManagerController::class, 'unassign'])->name('managers.unassign');
        Route::patch('services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');
        Route::resource('services', ServiceController::class);

        Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
        Route::resource('customers', CustomerController::class)->only(['index', 'show', 'edit', 'update']);

        Route::patch('requests/{maintenanceRequest}/approve', [AdminMaintenanceRequestController::class, 'approve'])->name('requests.approve');
        Route::patch('requests/{maintenanceRequest}/reject', [AdminMaintenanceRequestController::class, 'reject'])->name('requests.reject');
        Route::patch('requests/{maintenanceRequest}/request-info', [AdminMaintenanceRequestController::class, 'requestInfo'])->name('requests.request-info');
        Route::patch('requests/{maintenanceRequest}/appointment', [AdminMaintenanceRequestController::class, 'updateAppointment'])->name('requests.appointment');
        Route::patch('requests/{maintenanceRequest}/assign', [AdminMaintenanceRequestController::class, 'assign'])->name('requests.assign');
        Route::patch('requests/{maintenanceRequest}/unassign', [AdminMaintenanceRequestController::class, 'unassign'])->name('requests.unassign');
        Route::patch('requests/{maintenanceRequest}/book-appointment', [AdminMaintenanceRequestController::class, 'bookAppointment'])->name('requests.book-appointment');
        Route::patch('requests/{maintenanceRequest}/reschedule-appointment', [AdminMaintenanceRequestController::class, 'rescheduleAppointment'])->name('requests.reschedule-appointment');
        Route::patch('requests/{maintenanceRequest}/cancel-appointment', [AdminMaintenanceRequestController::class, 'cancelAppointment'])->name('requests.cancel-appointment');
        Route::patch('requests/{maintenanceRequest}/cancel', [AdminMaintenanceRequestController::class, 'cancelRequest'])->name('requests.cancel');
        Route::resource('requests', AdminMaintenanceRequestController::class)
            ->only(['index', 'show'])
            ->parameters(['requests' => 'maintenanceRequest']);

        Route::patch('technicians/{technician}/toggle-status', [TechnicianController::class, 'toggleStatus'])->name('technicians.toggle-status');
        Route::resource('technicians', TechnicianController::class);
        Route::get('technicians/{technician}/schedule/edit', [TechnicianScheduleController::class, 'edit'])->name('technicians.schedule.edit');
        Route::put('technicians/{technician}/schedule', [TechnicianScheduleController::class, 'update'])->name('technicians.schedule.update');

        Route::get('work-orders/{workOrder}', [AdminWorkOrderController::class, 'show'])->name('work-orders.show');
        Route::patch('work-orders/{workOrder}/correct', [AdminWorkOrderController::class, 'correct'])->name('work-orders.correct');

        Route::patch('inventory/{inventoryItem}/adjust', [InventoryItemController::class, 'adjust'])->name('inventory.adjust');
        Route::resource('inventory', InventoryItemController::class)->parameters(['inventory' => 'inventoryItem']);

        Route::post('invoices/work-orders/{workOrder}/generate', [AdminInvoiceController::class, 'generate'])->name('invoices.generate');
        Route::patch('invoices/{invoice}/issue', [AdminInvoiceController::class, 'issue'])->name('invoices.issue');
        Route::patch('invoices/{invoice}/discount', [AdminInvoiceController::class, 'discount'])->name('invoices.discount');
        Route::post('invoices/{invoice}/payments', [AdminInvoiceController::class, 'recordPayment'])->name('invoices.payments');
        Route::patch('payments/{payment}/confirm', [AdminInvoiceController::class, 'confirmPayment'])->name('payments.confirm');
        Route::patch('invoices/{invoice}/cancel', [AdminInvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::patch('invoices/{invoice}/close', [AdminInvoiceController::class, 'close'])->name('invoices.close');
        Route::resource('invoices', AdminInvoiceController::class)->only(['index', 'show']);

        Route::get('discount-approvals', [DiscountApprovalController::class, 'index'])->name('discount-approvals.index');
        Route::patch('discount-approvals/{discountApproval}/approve', [DiscountApprovalController::class, 'approve'])->name('discount-approvals.approve');
        Route::patch('discount-approvals/{discountApproval}/reject', [DiscountApprovalController::class, 'reject'])->name('discount-approvals.reject');

        Route::resource('reviews', AdminReviewController::class)->only(['index']);
    });
