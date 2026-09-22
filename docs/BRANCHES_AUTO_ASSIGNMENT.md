# Branches, Work Schedules & Automatic Technician Assignment

Design and implementation record for multi-branch operation, weekly technician
schedules, and the automatic assignment algorithm. Complements Epic 5
(Technicians), Epic 4 (request review) and Epic 6 (scheduling) — read those
for the pre-existing flows this builds on.

## 1. Database entities & relationships

```
branches 1───* cities              every city has exactly one branch
branches 1───* technicians         technician.branch_id is nullable:
                                   legacy/branchless technicians exist but
                                   are invisible to automatic assignment
technicians 1───* technician_schedules   one row per weekday (0 = Sunday)
```

### 1.1 `branches`

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK | Branch ID |
| `name` | `varchar(255)` | Unique | e.g. Riyadh, Jeddah |
| `priority` | `unsigned int` | Default `0` | Higher wins as overflow backup |
| `is_active` | `boolean` | Default `true` | Inactive branches never serve |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

### 1.2 `cities`

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK | City ID |
| `name` | `varchar(255)` | Unique (case-insensitive collation) | e.g. Riyadh |
| `branch_id` | `bigint unsigned` | FK → `branches.id` (`cascadeOnDelete`) | Responsible branch |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

City names are matched with `City::normalize()` (trim; the unique index
collation makes the lookup case-insensitive). Typing an already-served city
into another branch's form **moves** it, so a city never has two branches.

### 1.3 `technicians.branch_id`

Nullable FK → `branches.id` (`nullOnDelete`). Nullable for backward
compatibility with pre-branch technicians; the admin form offers a branch
dropdown, and automatic assignment only considers technicians with a branch.

### 1.4 `technician_schedules`

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK | Row ID |
| `technician_id` | `bigint unsigned` | FK → `technicians.id` (`cascadeOnDelete`) | Owner |
| `day_of_week` | `tinyint unsigned` | 0–6, Carbon convention (0 = Sunday) | Weekday |
| `is_working` | `boolean` | Default `true` | Day off when false |
| `start_time`, `end_time` | `time` | Nullable | Shift window (`H:i`) |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

Unique `(technician_id, day_of_week)`. A missing row means **off duty**.
`TechnicianSchedule::covers($start, $end)` checks shift containment
(start ≥ shift start, end ≤ shift end, `H:i` string comparison).

### 1.5 Eloquent relationships

- `Branch`: `technicians(): HasMany`, `cities(): HasMany`.
- `City`: `branch(): BelongsTo`, `City::normalize(string)`.
- `Technician`: `branch(): BelongsTo`, `schedules(): HasMany`.
- `TechnicianSchedule`: `technician(): BelongsTo`, `covers()`, `defaultWeek()`.

## 2. Weekly schedule design

New technicians receive the default template from
`TechnicianSchedule::defaultWeek()` inside `TechnicianService::create()`:

| Day | Hours |
|---|---|
| Monday–Thursday, Sunday | 08:00–17:00 |
| Friday | Off |
| Saturday | 10:00–18:00 |

Admins edit the full week on one page
(`admin/technicians/{technician}/schedule/edit`): per-day working checkbox
plus start/end times, validated (`H:i` shapes in the Form Request, start <
end enforced in `TechnicianService::syncSchedule()`, which replaces all
seven rows atomically and throws `ScheduleException` otherwise).

## 3. Automatic assignment algorithm

`TechnicianAssignmentService::autoAssign($request, $actor)` — isolated in
the existing assignment service per clean-architecture rules. Returns
`array{technician: ?Technician, reason: ?string}`: a hit, or a
human-readable reason while the request stays approved and unassigned
(surfaced to staff as an info flash; the Unassigned Jobs dashboard already
lists such requests, so no new status was introduced).

1. **State gate**: only `approved` requests (else `TechnicianAssignmentException`).
2. **Slot**: preferred date/time plus service duration → `[$date, $start, $end]`.
   Past/today slots are refused with a reason (`SchedulingService::book()`
   only books strictly future dates).
3. **City → branch**: normalize the request address city; unknown city →
   reason naming the city.
4. **Candidate branches**: the city's active branch first, then other active
   branches by `priority` DESC (explicit, documented cross-branch fallback).
5. **Per branch, rank candidates**: active profile + active user + skilled in
   the service category + belonging to the branch; then per-technician gates:
   schedule row exists and `covers()` the window → daily blocking-appointment
   count < 2 → no overlap via `SchedulingService::hasConflict()`. Skips are
   tallied by cause for the failure reason.
6. **Order**: daily load ASC, then total assigned-request count ASC (fairness),
   then id ASC (deterministic). The city branch is exhausted before
   fallbacks are tried.
7. **Commit under row locks**: candidates locked with `lockForUpdate()`,
   then daily load and overlap re-checked with locking reads
   (`hasConflictLocked()`), then the existing `assign()` books the slot
   (its own guards re-run as defense in depth). A lost race moves to the
   next candidate instead of failing.

### 3.1 Priority strategy

Correct branch/city first; within a branch the least-loaded technician
wins, so work spreads instead of piling onto one account; ties break by
total workload, then id — fully deterministic and test-covered.

### 3.2 Failure reasons (examples)

- `No branch serves the city 'Atlantis'.`
- `The preferred slot (2026-09-28 10:00) is in the past. Update it before assigning.`
- `No available technician in 'Riyadh' for Riyadh on 2026-09-28 10:00–11:00: 1 off duty, 2 at the daily limit of 2.`

## 4. Business rules & validation (BR-011…BR-016)

- **BR-011 — daily limit**: max 2 blocking appointments per technician per
  day (`TechnicianAssignmentService::MAX_DAILY_REQUESTS`), enforced in
  `autoAssign()`; `dailyLoad()` counts non-cancelled appointments.
- **BR-012 — working hours**: slot must sit inside the day's shift.
- **BR-013 — no overlap**: existing BR-002 conflict detection reused, plus
  locking re-checks for concurrency.
- **BR-014 — branch confinement**: candidates come from the responsible
  branch; other branches only as priority-ordered fallback.
- **BR-015 — approve-time automation**: approving a request attempts
  assignment (`Admin\MaintenanceRequestController::approve`); failures keep
  the request approved/unassigned with the reason flashed. Manual
  assign/reassign keeps its previous guards (skill/active/conflict) so
  existing flows are unchanged.
- **BR-016 — review gate preserved**: assignment runs on approval, not on
  creation, so unreviewed requests never consume calendars (documented
  deviation from a literal "assign on create", keeping the PRD workflow).

## 5. Concurrency protocol

Overlapping requests racing for one technician serialize on
`lockForUpdate()` technician rows taken in rank order; the loser re-checks
with current (locking) reads and moves on. MySQL `REPEATABLE READ`
snapshots are bypassed exactly where freshness matters. Covered by a
no-double-booking test; true parallel scheduling is a review concern, not
a unit-testable path in this suite.

## 6. Example scenario (Ahmed, Riyadh)

Ahmed works Mon–Thu + Sun 08:00–17:00, Sat 10:00–18:00, Friday off, limit 2/day.

| Request | Slot | Result |
|---|---|---|
| #101 Monday 09:00–11:00 | in hours, free | Assigned to Ahmed |
| #102 Monday 13:00–15:00 | in hours, free, load 1 | Assigned to Ahmed |
| #103 Monday 15:30–16:30 | no conflict but load 2 | Refused: daily limit (even without overlap) |
| #104 Friday 10:00–11:00 | day off | Refused: off duty |
| #105 Monday 18:00–19:00 | outside 08:00–17:00 | Refused: outside working hours |
| #106 Monday 10:30–11:30 | overlaps #101 | Refused: conflicting (or goes to a free colleague) |

## 7. Test cases (`tests/Feature/AutoAssignmentTest.php`, `BranchManagementTest.php`)
Happy-path booking; day off; outside hours; overlap pick-other/fail;
daily limit without overlap; load fairness + deterministic ties;
cross-branch priority fallback; unknown city; branchless technicians;
past slots; non-approved status throws; no double booking; approve-hook
flashes (assigned + skipped); branch CRUD incl. city moves and guarded
delete; technician branch persistence + default week; schedule replace +
start<end validation.

## 8. Customer reschedule loop

`autoAssign()` returns a machine-readable `code` alongside the reason:
`assigned`, `past_slot`, `no_city`, `no_branch`, `no_match`. When approval
fails with `no_match` or `past_slot`, the customer is notified
(`RescheduleNeeded`) and asked to pick another appointment time — other
codes are staff-side problems rescheduling cannot fix, so they only flash
for staff.

Customers move the preferred slot of their own approved, unassigned
requests (`PATCH requests/{maintenanceRequest}/reschedule`, future dates
only; foreign requests 404, assigned ones 403). Saving retries assignment
immediately: success assigns and books, failure keeps the new slot with the
reason flashed — the loop closes without staff involvement. Covered in
`tests/Feature/RescheduleFlowTest.php`.

## 8. Branch Managers

Each branch has at most one active manager (`branches.manager_user_id`,
nullable unique FK → `users.id`); a manager account manages at most one
branch at a time (same constraint, both directions). There is deliberately
only one manager role: assignment decides scope, so a manager running an
active branch is confined to `/branch/*` while unassigned managers keep the
legacy global staff access (`User::isBranchScoped()` is the single
predicate; `branch.scope` middleware enforces it on the admin area).
Super Admins (Admin/Manager staff) assign, change, or remove the manager on
the branch form — picking an existing active manager account or creating one
inline (name/email/password, role set automatically). Removing keeps the
account reusable elsewhere; nobody is ever demoted by the operation.

Managers work in a dedicated `/branch/*` area (`role:manager`):
dashboard (scoped performance overview via `BranchService::overview()`),
technicians (list/view of their branch), schedules (view/edit), and requests
(list/view/review/assign/unassign scoped to served cities, with automatic
assignment on approval working exactly as in §3).

Authorization is branch-scoped, not just role-scoped: every branch
controller resolves the manager's active branch and returns 404 for foreign
technicians, requests, and schedules, so cross-branch data is unreachable
rather than merely forbidden. A manager whose branch is deactivated loses
area access the same way. Global roles are untouched — the new value is
denied by every existing allowlist by default.

Super Admins also get a central `admin/managers` page listing every manager
account with its branch, employment toggle, and unassign action, plus an
unmanaged-branches alert on the admin dashboard linking straight to the
branch form. Manager accounts themselves are fully editable there
(name, email, optional password reset, status, branch moves with
occupied-branch and duplicate-email guards).
