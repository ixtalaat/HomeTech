<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Models\DiscountApproval;
use App\Services\InvoiceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountApprovalController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private InvoiceService $invoices) {}

    /**
     * Display pending discount approvals for managers.
     */
    public function index(): View
    {
        $approvals = DiscountApproval::pending()
            ->with(['invoice.user', 'requester'])
            ->latest()
            ->paginate(15);

        return view('admin.discount-approvals.index', compact('approvals'));
    }

    /**
     * Approve a queued discount and apply it.
     */
    public function approve(Request $request, DiscountApproval $discountApproval): RedirectResponse
    {
        try {
            $this->invoices->approveDiscountRequest($discountApproval, $request->user());
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Discount approved and applied to the invoice.');
    }

    /**
     * Reject a queued discount.
     */
    public function reject(Request $request, DiscountApproval $discountApproval): RedirectResponse
    {
        try {
            $this->invoices->rejectDiscountRequest($discountApproval, $request->user());
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Discount request rejected.');
    }
}
