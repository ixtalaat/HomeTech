<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Exceptions\BillingException;
use App\Exceptions\StripeException;
use App\Http\Requests\MakePaymentRequest;
use App\Models\Invoice;
use App\Services\PaymentService;
use App\Services\StripeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PaymentService $payments,
        private StripeService $stripe
    ) {}

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
     *
     * Card payments are processed securely through Stripe Checkout;
     * cash and bank transfers are recorded directly.
     */
    public function pay(MakePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->isOwnedBy($request->user()), 404);

        $validated = $request->validated();
        $method = PaymentMethod::from($validated['method']);

        if ($method === PaymentMethod::Card) {
            return $this->startCardPayment($request, $invoice, (float) $validated['amount']);
        }

        try {
            $this->payments->pay(
                $invoice,
                (float) $validated['amount'],
                $method,
                $request->user()
            );
        } catch (BillingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Payment received. Thank you!');
    }

    /**
     * Start a Stripe Checkout Session for the invoice balance.
     */
    public function stripeCheckout(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->isOwnedBy($request->user()), 404);
        $this->authorize('pay', $invoice);

        return $this->startCardPayment($request, $invoice, $invoice->remaining());
    }

    /**
     * Start a card payment for the given amount via Stripe Checkout.
     */
    private function startCardPayment(Request $request, Invoice $invoice, float $amount): RedirectResponse
    {
        try {
            $session = $this->stripe->checkout($invoice, $request->user(), $amount);
        } catch (StripeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->away($session->url);
    }

    /**
     * Settle a completed Stripe Checkout Session (idempotent).
     */
    public function stripeSuccess(Request $request): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');

        if ($sessionId === '') {
            return redirect()->route('invoices.index')->with('error', 'Missing Stripe session.');
        }

        try {
            $payment = $this->stripe->handleSuccess($sessionId, $request->user());
        } catch (StripeException $exception) {
            return redirect()->route('invoices.index')->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('invoices.show', $payment->invoice_id)
            ->with('success', 'Online payment received. Thank you!');
    }

    /**
     * Return from a cancelled Stripe Checkout Session.
     */
    public function stripeCancel(Invoice $invoice): RedirectResponse
    {
        return redirect()
            ->route('invoices.show', $invoice)
            ->with('error', 'Online payment was cancelled. No charge was made.');
    }
}
