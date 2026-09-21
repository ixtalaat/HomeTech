<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display the user's notifications, newest first.
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        $request->user()->unreadNotifications->markAsRead();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a single notification as read and follow its request link.
     */
    public function show(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless($notification->notifiable_id === $request->user()->id, 404);

        $notification->markAsRead();

        $requestId = $notification->data['maintenance_request_id'] ?? null;

        if ($requestId === null && isset($notification->data['invoice_id'])
            && in_array($request->user()->role, [UserRole::Admin, UserRole::Manager], true)) {
            return redirect()->route('admin.invoices.show', $notification->data['invoice_id']);
        }

        if ($requestId === null) {
            return redirect()->route('notifications.index');
        }

        if ($request->user()->role === UserRole::Technician) {
            return redirect()->route('technician.jobs.index');
        }

        if (in_array($request->user()->role, [UserRole::Admin, UserRole::Manager], true)) {
            return redirect()->route('admin.requests.show', $requestId);
        }

        return redirect()->route('requests.show', $requestId);
    }
}
