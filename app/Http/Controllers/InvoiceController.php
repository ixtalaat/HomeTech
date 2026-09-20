<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Exceptions\BillingException;
use App\Http\Requests\MakePaymentRequest;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private PaymentService $payments) {}

    /**
     * Display a listing of the customer's invoices.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::ownedBy($request->user()->id)
            ->with('request')
            ->latest()
            ->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    /**
     * Display the specified invoice.
     */
    public function show(Request $request, Invoice $invoice): View
    {
        abort_unless($invoice->isOwnedBy($request->user()), 404);
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'payments', 'request.service']);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Pay the invoice (full or partial).
     */
    public function pay(MakePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->isOwnedBy($request->user()), 404);

        try {
            $validated = $request->validated();

            $this->payments->pay(
                $invoice,
                (float) $validated['amount'],
                PaymentMethod::from($validated['method']),
                $request->user()
            );
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Payment received. Thank you!');
    }
}
