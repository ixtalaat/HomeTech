<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApplyDiscountRequest;
use App\Http\Requests\Admin\RecordPaymentRequest;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private InvoiceService $invoices,
        private PaymentService $payments
    ) {}

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::with(['user', 'request'])->latest();

        if ($request->filled('status')) {
            $status = InvoiceStatus::tryFrom($request->input('status'));

            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($inner) use ($search): void {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery): Builder => $userQuery->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->paginate(15)->withQueryString();
        $statuses = InvoiceStatus::cases();

        return view('admin.invoices.index', compact('invoices', 'statuses'));
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'payments.receiver', 'user', 'request.service']);

        return view('admin.invoices.show', compact('invoice'));
    }

    /**
     * Generate an invoice from a completed work order.
     */
    public function generate(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('manage', Invoice::class);

        try {
            $invoice = $this->invoices->generate($workOrder, $request->user());
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->number} generated as draft.");
    }

    /**
     * Issue a draft invoice.
     */
    public function issue(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('manage', $invoice);

        try {
            $this->invoices->issue($invoice, $request->user());
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Invoice {$invoice->number} issued.");
    }

    /**
     * Apply a discount to the invoice.
     */
    public function discount(ApplyDiscountRequest $request, Invoice $invoice): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->invoices->applyDiscount(
                $invoice,
                $request->user(),
                DiscountType::from($validated['discount_type']),
                (float) $validated['discount_value']
            );
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Discount applied successfully.');
    }

    /**
     * Record a payment against the invoice.
     */
    public function recordPayment(RecordPaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->payments->pay(
                $invoice,
                (float) $validated['amount'],
                PaymentMethod::from($validated['method']),
                $request->user(),
                $validated['reference'] ?? null
            );
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Payment recorded successfully.');
    }

    /**
     * Cancel an unpaid invoice.
     */
    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('manage', $invoice);

        try {
            $this->invoices->cancelInvoice($invoice, $request->user());
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Invoice {$invoice->number} cancelled.");
    }

    /**
     * Close a fully paid invoice.
     */
    public function close(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('manage', $invoice);

        try {
            $this->invoices->markClosed($invoice, $request->user());
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Invoice {$invoice->number} closed.");
    }
}
