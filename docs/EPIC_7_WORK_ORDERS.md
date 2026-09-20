# Epic 7 — Work Orders

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 7 (Work Orders)** in HomeTech.

---

## 1. Domain Entities & Database Schema

### 1.1 Work Orders (`work_orders`)
The technician's execution record for a scheduled request (PRD §14).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Work Order ID |
| `maintenance_request_id` | `bigint unsigned` | Unique, FK -> `maintenance_requests.id` (`cascadeOnDelete`) | Parent request (one order per request) |
| `appointment_id` | `bigint unsigned` | Nullable, FK -> `appointments.id` (`nullOnDelete`) | Booked visit |
| `technician_id` | `bigint unsigned` | FK -> `technicians.id` (`restrictOnDelete`) | Performing technician |
| `status` | `varchar(30)` | Index, Default `'open'` | Current `WorkOrderStatus` value |
| `diagnosis` | `text` | Nullable | Technician diagnosis (required to complete) |
| `work_notes` | `text` | Nullable | Work performed/problems/recommendations (required to complete) |
| `before_photos` / `after_photos` | `json` | Nullable | Stored photo paths |
| `started_at` / `completed_at` | `timestamp` | Nullable | Visit timestamps |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

### 1.2 Labor Items (`labor_items`)
Itemized labor charges (PRD §14, §17).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Labor Item ID |
| `work_order_id` | `bigint unsigned` | FK -> `work_orders.id` (`cascadeOnDelete`) | Parent work order |
| `description` | `varchar(255)` | Not Null | Labor description (e.g. "Capacitor Repair") |
| `cost` | `decimal(10,2)` | Default `0` | Labor cost in EGP |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

Totals via `WorkOrder::laborTotal()` (`sum('cost')`).

### 1.3 Audit Logs (`audit_logs`)
Append-only record for authorized corrections and future financial/inventory changes (PRD §27). Shared by Epics 8/10/12.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Log ID |
| `actor_id` | `bigint unsigned` | Nullable, FK -> `users.id` (`nullOnDelete`) | Staff member responsible |
| `action` | `varchar(100)` | Not Null | Action key (e.g. `work_order.corrected`) |
| `subject_type` / `subject_id` | `string` / `bigint` | Indexed | Polymorphic subject |
| `changes` | `json` | Nullable | Old/new field values |
| `reason` | `text` | Nullable | Required reason |

`AuditLog::record($actor, $action, $subject, $changes, $reason)` helper.

### 1.4 `WorkOrderStatus` Enum
`open`, `in_progress`, `completed` + `label()`.

---

## 2. Lifecycle & Completion Guard

`App\Services\WorkOrderService` is the single choke point:

- `startVisit($request, $actor)` — guards: request is `scheduled`, actor is the assigned technician (or staff), appointment exists and passes `SchedulingService::start()` (cancelled ⇒ refuse). Creates (idempotent `firstOrCreate`) the order with `started_at`, transitions request `scheduled → in_progress`.
- `complete()` — runs `WorkOrder::unmetCompletionRequirements()` (diagnosis + notes required, PRD §22) and throws `WorkOrderException` listing blockers. On pass: stamps `completed_at`, appointment → `completed`, request → `completed`.
- Recording methods (`recordDiagnosis/recordNotes/addLaborItem/uploadPhotos`) all funnel through the BR-007 edit guard.
- Unlocked edges: `scheduled → in_progress`, `in_progress → completed`. `waiting_customer_approval` cycle belongs to Epic 9; materials hook to Epic 8.

---

## 3. Business Rules & Security

1. **Completion requirements (§22)**: diagnosis + work notes are hard blockers; labor is optional-but-totaled; the materials/additional-work hooks are extension points for Epics 8–9.
2. **Completed job protection (BR-007)**: every mutation throws `CompletedWorkOrderException` for non-staff on completed orders; `WorkOrderPolicy::update` also returns false, so technician forms disappear and routes 403.
3. **Authorized corrections**: `correct()` is staff-only, limited to diagnosis/notes fields, requires a reason, and writes an `AuditLog` row with the field diff — visible on the admin work-order page.
4. **Technician isolation**: portal queries scope to the technician's own assignments (`ownsWorkOrder` 404s otherwise); policy distinguishes owner vs staff.
5. **Photos**: same validation as request photos (`image, mimes jpg/jpeg/png,webp, max 2048`, ≤5 per upload), stored under `work-orders/{id}/before|after` with hashed names.

---

## 4. Endpoints & Route Map

### Technician Portal (`auth` + `role:technician`, `technician.` prefix):
- `GET /technician/jobs` (`technician.jobs.index`) — Scheduled requests awaiting start + paginated work orders.
- `GET /technician/jobs/{workOrder}` (`technician.jobs.show`) — Diagnosis/notes/photos/labor forms + completion.
- `POST /technician/jobs/requests/{maintenanceRequest}/start` — Start visit.
- `PATCH .../diagnosis|notes` — Record diagnosis / notes.
- `POST .../labor` — Add labor item (`description`, `cost ≥ 0`).
- `POST .../photos` — Upload (`slot: before|after`, photos array).
- `PATCH .../complete` — Complete (guarded).

### Admin (`admin.` prefix):
- `GET /admin/work-orders/{workOrder}` (`admin.work-orders.show`) — Details, labor totals, correction form, audit trail (linked from the admin request page).
- `PATCH /admin/work-orders/{workOrder}/correct` — Logged correction (`reason` required).

---

## 5. Test Coverage

`tests/Feature/WorkOrderTest.php`:
- Start visit happy path (order + request → `in_progress`); refused for wrong technician, non-scheduled request, and cancelled appointment.
- Completion blocked with no diagnosis/notes (exact blocker messages), passes when met; appointment + request flip to `completed`.
- Labor totals exact to the decimal (100.00 + 150.50 = 250.50).
- Photo upload validation (bad `slot` rejected; valid PNG stored in `before_photos`).
- BR-007: technician edit on completed order throws; non-staff correction throws; staff correction applies + writes an `AuditLog` row with actor and reason.
- Portal isolation: technician sees own jobs, customer gets 403, cross-technician `show` 404s, premature completion surfaces an `error` flash.

Shared helpers (`skilledTechnician`, `fakePngPhoto`, `scheduledRequest`) consolidated in `tests/Pest.php` so single-file runs work.
