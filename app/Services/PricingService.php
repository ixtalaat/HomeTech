<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\UserRole;
use App\Exceptions\BillingException;
use App\Models\User;
use App\Models\WorkOrder;

class PricingService
{
    /**
     * Break down the actual costs of a work order with each component tracked.
     *
     * @return array{service_base: float, labor: float, materials: float, additional: float, subtotal: float}
     */
    public function breakdown(WorkOrder $workOrder): array
    {
        $serviceBase = (float) $workOrder->request->service->base_price;
        $labor = $workOrder->laborTotal();
        $materials = $workOrder->materialsTotal();
        $additional = (float) $workOrder->additionalWorkItems()->billable()->sum('cost');

        return [
            'service_base' => round($serviceBase, 2),
            'labor' => round($labor, 2),
            'materials' => round($materials, 2),
            'additional' => round($additional, 2),
            'subtotal' => round($serviceBase + $labor + $materials + $additional, 2),
        ];
    }

    /**
     * Compute a discount amount, enforcing the non-negative total and the
     * manager-approval gate for high-value discounts.
     *
     * @throws BillingException
     */
    public function discountAmount(float $subtotal, DiscountType $type, float $value, User $actor): float
    {
        $amount = $this->computeAmount($subtotal, $type, $value);

        if ($this->requiresManagerApproval($type, $value) && $actor->role !== UserRole::Manager) {
            throw new BillingException(__('This discount requires manager approval.'));
        }

        return $amount;
    }

    /**
     * Compute a discount amount with math-only guards (no approval gate).
     *
     * @throws BillingException
     */
    public function computeAmount(float $subtotal, DiscountType $type, float $value): float
    {
        if ($value < 0) {
            throw new BillingException(__('Discount value cannot be negative.'));
        }

        if ($type === DiscountType::Percent && $value > 100) {
            throw new BillingException(__('Percentage discount cannot exceed 100%.'));
        }

        $amount = $type === DiscountType::Percent
            ? round($subtotal * $value / 100, 2)
            : round($value, 2);

        if ($amount > $subtotal) {
            throw new BillingException(__('Discount cannot make the invoice total negative.'));
        }

        return $amount;
    }

    /**
     * Determine whether the discount needs a manager.
     */
    public function requiresManagerApproval(DiscountType $type, float $value): bool
    {
        return $type === DiscountType::Percent
            ? $value > config('billing.manager_discount_percent_over', 20)
            : $value > config('billing.manager_discount_fixed_over', 500);
    }
}
