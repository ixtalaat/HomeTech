<?php

return [
    'request_submitted' => 'تم تقديم طلب الصيانة #:id وهو بانتظار المراجعة.',
    'request_approved' => 'تمت الموافقة على طلب الصيانة #:id.',
    'technician_assigned' => 'تم تعيين الفني :name لطلبك #:id.',
    'appointment_changed' => 'تغيّر موعد طلبك #:id: :summary.',
    'invoice_issued' => 'تم إصدار الفاتورة :number (:total جنيه) لطلبك #:id.',
    'payment_received' => 'تم استلام دفعة :amount جنيه للفاتورة :number. المتبقي: :remaining جنيه.',
    'job_completed' => 'اكتمل العمل في طلبك #:id.',
    'job_assigned' => 'مهمة جديدة مُسندة: الطلب #:id (:service).',
    'job_rescheduled' => 'أُعيدت جدولة المهمة #:id: :summary.',
    'job_cancelled' => 'أُلغيت المهمة #:id: :reason.',
    'additional_work_approval' => 'مطلوب موافقة: :description (:cost جنيه) للطلب #:id.',
    'additional_work_approved' => 'وافق العميل على العمل الإضافي \':description\' للطلب #:id.',
    'additional_work_rejected' => 'رفض العميل العمل الإضافي \':description\' للطلب #:id.',
    'technician_on_way' => ':technician في الطريق لطلبك #:id.',
    'discount_approval_requested' => 'مطلوب موافقة على خصم: :value :type للفاتورة :number.',
    'queue_backlog_stuck' => 'تكدس في الطابور: :count مهام معلقة، وأقدمها ينتظر منذ :minutes دقيقة.',
    'stale_assignment_nudge' => 'الطلب #:id تمت الموافقة عليه منذ :days أيام وما زال غير مُسند.',
    'reschedule_needed' => 'لا يوجد فني متاح للطلب #:id. يرجى اختيار موعد آخر.',
    'job_unassigned' => 'تم إلغاء تعيينك من الطلب #:id.',
    'fallback' => 'تحديث',
];
