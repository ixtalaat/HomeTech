# Epic 6 — Scheduling

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 6 (Scheduling)** in HomeTech.

---

## 1. Domain Entities & Database Schema

### 1.1 Appointments (`appointments`)
One live booking row per maintenance request (PRD §13).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Appointment ID |
| `maintenance_request_id` | `bigint unsigned` | Unique, FK -> `maintenance_requests.id` (`cascadeOnDelete`) | Parent request (one row per request) |
| `technician_id` | `bigint unsigned` | FK -> `technicians.id` (`restrictOnDelete`) | Assigned technician |
| `date` | `date` | Not Null | Appointment date (must be future) |
| `start_time` / `end_time` | `time` | Not Null | Time window (`end > start`) |
| `status` | `varchar(30)` | Index, Default `'scheduled'` | Current `AppointmentStatus` value |
| `notes` | `text` | Nullable | Scheduling notes |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

**Indexes**: `[technician_id, date]` (conflict lookups), `status`.

Reschedules mutate the same row; the `maintenance_request_status_histories` table keeps the trail.

### 1.2 `AppointmentStatus` Enum
`scheduled`, `confirmed`, `rescheduled`, `cancelled`, `completed` + `label()`. `confirmed`/`completed` transitions belong to Epic 7 (visit lifecycle).

### 1.3 `Appointment` Model
`request()/technician()` relations; scopes `forTechnicianOn()`, `blocking()` (excludes `cancelled`), `overlapping($start, $end)` (`start_time < $end AND end_time > $start` — boundary-touching windows do not overlap); `isCancelled()` helper. `MaintenanceRequest::appointment(): HasOne` added.

---

## 2. Scheduling Service & Conflict Detection (BR-002)

`App\Services\SchedulingService` owns all calendar writes:

- `hasConflict($techId, $date, $start, $end, $ignoreId)` — same technician + same date + window overlap, ignoring `cancelled` rows (a cancelled slot frees the calendar) and optionally self on reschedule.
- `book()` — guards: request is `technician_assigned`, future slot, `end > start`, no conflict → creates the row + transitions the request to `scheduled`.
- `reschedule()` — refuses cancelled appointments, re-runs the conflict check excluding self → updates + marks appointment `rescheduled` + history entry (request stays `scheduled`).
- `cancel()` — appointment → `cancelled`, request → `technician_assigned` for rebooking (history recorded).
- `start()` — **guard only**: throws `CancelledAppointmentException` for cancelled appointments (PRD §13). Epic 7's visit-start calls it.

### Auto-booking on assignment
`TechnicianAssignmentService::assign()` now takes an optional `$slot` (default: preferred date/time, end derived from `service.estimated_duration_minutes`), pre-checks the conflict, and books inside the **same transaction** — a conflicting preferred slot fails the whole assignment with a clear message, never leaving a half-assigned request. Related consistency fixes: `reassign()` moves the appointment to the new technician (conflict-checked, so the calendar never holds a stale booking); `unassign()` cancels the appointment and returns the request to `approved`.

### Unlocked status edges
`technician_assigned → scheduled`, `scheduled → technician_assigned`, `scheduled → approved`. `scheduled → in_progress` stays locked for Epic 7.

---

## 3. Business Rules & Security

1. **No overlapping appointments (BR-002)**: enforced in the service on create and reschedule — including the PRD §12 example (09:00–11:00 booked ⇒ 10:00–12:00 rejected) while adjacent slots (11:00–13:00) are allowed.
2. **Cancelled appointments cannot be started** (PRD §13): `start()` guard, pinned by test.
3. **Staff-only booking management**: new `manageAppointment` policy ability (staff + status in `approved/technician_assigned/scheduled`) covers assign/unassign/book/cancel; book/reschedule FormRequests additionally pin the exact expected state.
4. **Future slots only**, validated (`after:today`, `end after start`) and re-checked in the service.

---

## 4. Endpoints & Route Map (all `admin.`)

- `PATCH /admin/requests/{maintenanceRequest}/book-appointment` — Book a slot for a `technician_assigned` request.
- `PATCH /admin/requests/{maintenanceRequest}/reschedule-appointment` — Move the slot (conflict re-checked).
- `PATCH /admin/requests/{maintenanceRequest}/cancel-appointment` — Cancel, request back to `technician_assigned`.
- Existing `assign` now books automatically and also throws a catchable `SchedulingConflictException` on conflict.

The admin request page shows per-state UI: assign-and-book form (`approved`), book-slot form (`technician_assigned`), appointment card with reschedule/cancel/reassign (`scheduled`). The customer request page shows the scheduled visit box; history rows narrate every booking change.

---

## 5. Test Coverage

- `tests/Feature/SchedulingTest.php`
  - BR-002 verbatim: 10:00–12:00 over 09:00–11:00 rejected; adjacent boundary, other technician, and other date allowed; cancelled slot reusable.
  - HTTP reschedule (sets `rescheduled` + date); reschedule into conflict rejected with record unchanged.
  - HTTP cancel → `technician_assigned` + appointment `cancelled`; starting a cancelled appointment throws.
  - Conflicting preferred slot fails the whole assignment (status stays `approved`, no technician, no appointment row).
  - Booking end derived from service duration (10:00 + 90 min → 11:30).
- `tests/Feature/TechnicianAssignmentTest.php` updated to the new lifecycle (assign ends at `scheduled` with a booked slot; reassign moves the appointment; unassign cancels it).
- Shared `skilledTechnician()` helper moved to `tests/Pest.php`.
