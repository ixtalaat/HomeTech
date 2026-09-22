<?php

return [
    'request_submitted' => 'Your maintenance request #:id was submitted and is pending review.',
    'request_approved' => 'Your maintenance request #:id was approved.',
    'technician_assigned' => 'Technician :name was assigned to your request #:id.',
    'appointment_changed' => 'The appointment for your request #:id changed: :summary.',
    'invoice_issued' => 'Invoice :number (:total EGP) was issued for your request #:id.',
    'payment_received' => 'Payment of :amount EGP received for invoice :number. Remaining: :remaining EGP.',
    'job_completed' => 'The work for your request #:id is completed.',
    'job_assigned' => 'New job assigned: request #:id (:service).',
    'job_rescheduled' => 'Job #:id rescheduled: :summary.',
    'job_cancelled' => 'Job #:id was cancelled: :reason.',
    'additional_work_approval' => 'Approval needed: :description (:cost EGP) for request #:id.',
    'additional_work_approved' => 'The customer approved the additional work \':description\' for request #:id.',
    'additional_work_rejected' => 'The customer rejected the additional work \':description\' for request #:id.',
    'technician_on_way' => ':technician is on the way for your request #:id.',
    'discount_approval_requested' => 'Discount approval needed: :value :type on invoice :number.',
    'queue_backlog_stuck' => 'Queue backlog: :count jobs pending, oldest waiting :minutes minutes.',
    'stale_assignment_nudge' => 'Request #:id approved :days days ago is still unassigned.',
    'reschedule_needed' => 'No technician is available for request #:id. Please choose another appointment time.',
    'fallback' => 'Update',
];
