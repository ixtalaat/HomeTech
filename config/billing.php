<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Manager Discount Approval Gate
    |--------------------------------------------------------------------------
    |
    | Discounts above these thresholds require a manager (admins are refused).
    | Percent applies to the invoice subtotal; fixed is a flat EGP amount.
    |
    */

    'manager_discount_percent_over' => 20,
    'manager_discount_fixed_over' => 500,

    /*
    |--------------------------------------------------------------------------
    | Cancellation Policy (BR-008)
    |--------------------------------------------------------------------------
    |
    | Appointments further out than the free window cancel for free; later
    | ones carry a percentage fee of the service base estimate. Requests
    | past the visit-start point cannot be cancelled at all.
    |
    */

    'free_cancellation_hours' => 24,
    'late_cancellation_fee_percent' => 10,
];
