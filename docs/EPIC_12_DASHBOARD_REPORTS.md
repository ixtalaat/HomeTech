# Epic 12 — Dashboard & Reports

This document describes the technical architecture, notifications, dashboard, reports, and audit coverage implemented for **Epic 12 (Dashboard & Reports)** in HomeTech — the final MVP epic.

---

## 1. Notifications (12.1, PRD §24)

Database channel (the `notifications` table migration from Epic 9). All classes implement `ShouldQueue` — delivered by the `queue:listen` worker already in `composer run dev`, while tests stay synchronous via the `sync` queue driver in phpunit.

| Recipient | Class | Fired from |
|---|---|---|
| Customer | `RequestSubmitted` | `MaintenanceRequestService::create` |
| Customer | `RequestApproved` | `RequestReviewService::approve` |
| Customer | `TechnicianAssignedToRequest` | `TechnicianAssignmentService::{assign,reassign}` |
| Customer | `AppointmentChanged` | `SchedulingService::reschedule` |
| Customer | `InvoiceIssued` | `InvoiceService::issue` |
| Customer | `PaymentReceived` | `PaymentService::pay` |
| Customer | `JobCompleted` | `WorkOrderService::complete` |
| Customer | `AdditionalWorkRequiresApproval` | Epic 9 |
| Technician | `JobAssigned` | `TechnicianAssignmentService::{assign,reassign}` |
| Technician | `JobRescheduled` | `SchedulingService::reschedule` |
| Technician | `JobCancelled` | `SchedulingService::cancel`, `CancellationService::cancel` |
| Technician | `AdditionalWorkDecided` | Epic 9 |

("Technician on the way" has no producing transition yet, so no class — it arrives with that status.)

**Inbox**: header bell with unread count, `GET /notifications` list (marks read on open), `GET /notifications/{notification}` marks one read and deep-links by role (customer → request, technician → jobs, staff → admin request).

---

## 2. Admin Dashboard (12.2, PRD §25)

`Admin\DashboardController@index` now owns the `admin.dashboard` route: cards for today's jobs, pending requests, active jobs, completed today, unpaid invoices (+ outstanding total), low-stock items (+ names); lists for upcoming appointments (7 days), unassigned jobs, jobs awaiting customer approval, and recent requests. All data assembled by `ReportingService::dashboard()`.

---

## 3. Reports (12.3, PRD §26)

`Admin\ReportController` + `ReportingService`, one page each (tables, no chart library in MVP):

- **Revenue** — daily (30d) and monthly (12m) from payments; by service (invoice lines); by technician (paid amounts on their requests); outstanding invoices with balances.
- **Jobs** — counts by status and by service; average completion time from `started_at → completed_at`.
- **Technicians** — assigned/completed/cancelled counts, billed revenue, average rating + review count.
- **Inventory** — current stock, low-stock list, most-used ranking (consumption ledger), recent movements.

---

## 4. Audit Logging (12.4, BR-012)

The `audit_logs` table + `AuditLog::record()` (Epic 7) already covered invoice generate/issue/discount/cancel, payment receipt, inventory adjustments, and work-order corrections. This epic closes the gaps: `additional_work.decided` (actor + verdict) and `request.cancelled` (actor + fee + reason). Invoice edits need no extra code (one-shot generation; discounts logged) and payments are append-only by design (logged at receipt).

---

## 5. Test Coverage

- `tests/Feature/NotificationTest.php` — `Notification::fake()` asserts the exact class per lifecycle event across a full submit→paid journey, technician cancellation notice, and inbox read semantics.
- `tests/Feature/DashboardReportsTest.php` — dashboard + all four reports load for staff (403 otherwise); revenue totals, outstanding emptiness, job counts, completion average, and per-technician revenue asserted against seeded flows.
- `tests/Feature/AuditLogTest.php` — one matching row (actor/subject/reason) for discount, payment, adjustment, correction, decision, and cancellation.
