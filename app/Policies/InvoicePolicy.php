<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $invoice->isOwnedBy($user) || $this->isStaff($user);
    }

    /**
     * Determine whether the user can manage billing (generate/issue/discount/cancel/close).
     */
    public function manage(User $user, ?Invoice $invoice = null): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can pay the invoice.
     */
    public function pay(User $user, Invoice $invoice): bool
    {
        return ($invoice->isOwnedBy($user) || $this->isStaff($user)) && $invoice->acceptsPayments();
    }

    /**
     * Determine whether the user is admin or manager staff.
     */
    private function isStaff(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
