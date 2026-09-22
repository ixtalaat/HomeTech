# Epic 5 — Technicians

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 5 (Technicians)** in HomeTech.

---

## 1. Domain Entities & Database Schema

### 1.1 Technicians (`technicians`)
Employment profile extending the technician's `users` row (1–1), per PRD §11.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Technician ID |
| `user_id` | `bigint unsigned` | Unique, FK -> `users.id` (`cascadeOnDelete`) | Linked user account (`role = technician`) |
| `phone` | `varchar(30)` | Nullable | Work contact phone |
| `emergency_contact` | `varchar(255)` | Nullable | Emergency contact |
| `is_active` | `tinyint(1)` | Index, Default `1` | Employment status |
| `notes` | `text` | Nullable | Internal notes |
| `hired_at` | `date` | Nullable | Hire date |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

`Technician` model conventions:
- `user(): BelongsTo`, `categories(): BelongsToMany`, `assignedRequests(): HasMany(MaintenanceRequest)`.
- `scopeActive()`, `supportsCategory($id)`, and `isAvailableFor(ServiceCategory)` — the shared BR-001 predicate (active profile + active user account + supports category). Epic 6 conflict detection will call it too.
- `User::technician(): HasOne` added for the reverse lookup.
- `role` added to `User::$fillable` (assigned explicitly by code only, never from user input — registration still relies on the `customer` DB default).

### 1.2 Skills Pivot (`category_technician`)
Links technicians to the service categories they support (PRD §11).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `technician_id` | `bigint unsigned` | PK, FK -> `technicians.id` (`cascadeOnDelete`) | Technician |
| `service_category_id` | `bigint unsigned` | PK, FK -> `service_categories.id` (`cascadeOnDelete`) | Supported category |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps (pivot `withTimestamps()`) |

Composite primary key enforces one skill row per technician/category. Managed via `categories()->sync()` on the technician edit form — no separate skills controller.

### 1.3 Assignment Link (`maintenance_requests.technician_id`)
Nullable FK -> `technicians.id` (`nullOnDelete`): the *current* assignee. Every assignment change also appends a `maintenance_request_status_histories` row for the audit trail.

---

## 2. Assignment Logic (BR-001)

`App\Services\TechnicianAssignmentService` is the single choke point for assignment, with ordered guards (each raising `TechnicianAssignmentException` with a specific message):

1. **Request state**: only `approved` requests can be assigned — `rejected`, `cancelled`, `pending_review`, etc. are refused (PRD §10: rejected requests cannot be assigned).
2. **Active technician**: profile `is_active` AND linked user `is_active` must both hold.
3. **Skill match (BR-001)**: technician must support the request's service category.

Operations (all transactional):
- `assign()` — sets `technician_id` + transitions `approved → technician_assigned` via `RequestStatusService` (edge unlocked by this epic).
- `reassign()` — for already-assigned requests; swaps `technician_id` and records a same-status history entry.
- `unassign()` — clears `technician_id`, transitions back to `approved` (edge `technician_assigned → approved` unlocked).
- `eligibleFor($request)` — pre-filtered, least-loaded-first list (active profile + active user + supports category + workload count) powering the admin dropdown.

---

## 3. Business Rules & Security

1. **Skill matching (BR-001)**: enforced in the service, never just hidden in the UI — the dropdown is pre-filtered but the guard re-checks.
2. **Staff-only management**: `TechnicianPolicy` (staff-only `viewAny/view/create/update/toggleStatus`) registered via `Gate::policy()` (auto-discovery cannot find it — same as Epics 3–4 policies), plus the existing `role:admin,manager` middleware.
3. **Atomic technician creation**: user account + profile + skill sync in one `DB::transaction`; email unique; admin sets the initial password (no mail infra in MVP).
4. **Safe removal**: technicians with assigned requests cannot be deleted (deactivate instead); otherwise profile is deleted and the user account deactivated.

---

## 4. Endpoints & Route Map

### Admin / Manager Routes (`auth` + `role:admin,manager`, `admin.` prefix):
- `GET /admin/technicians` (`admin.technicians.index`) — List with search + status filter, skills, workload count.
- `GET /admin/technicians/create` + `POST /admin/technicians` — Create (account + profile + skills).
- `GET /admin/technicians/{technician}` (`admin.technicians.show`) — Profile, skills, recent assignments.
- `GET /admin/technicians/{technician}/edit` + `PUT` — Update + skill sync.
- `PATCH /admin/technicians/{technician}/toggle-status` — Activate/deactivate.
- `DELETE /admin/technicians/{technician}` — Safe remove.
- `PATCH /admin/requests/{maintenanceRequest}/assign` (`admin.requests.assign`) — Assign/reassign (`AssignTechnicianRequest`: valid `technician_id`).
- `PATCH /admin/requests/{maintenanceRequest}/unassign` (`admin.requests.unassign`) — Back to approved.

The admin request show page (Epic 4) gained an assignment section visible for `approved`/`technician_assigned` requests, with the eligible-technician dropdown and unassign action.

---

## 5. Test Coverage

Pest feature tests:
- `tests/Feature/Admin/TechnicianTest.php`
  - Non-staff (customer/technician) get `403` on all technician routes.
  - Admin creates technician (user with `technician` role + profile + skills).
  - Skill sync replaces old skills; status toggle works.
- `tests/Feature/TechnicianAssignmentTest.php`
  - Happy path: eligible technician assigned to approved request → `technician_assigned` + `technician_id` set.
  - BR-001: unskilled technician refused, request unchanged.
  - Rejected/cancelled/pending requests refused (`error` flash, no assignment).
  - Inactive profile and inactive user account both refused.
  - Reassign swaps technician with history; unassign returns to approved.
  - `eligibleFor()` includes only skilled + active technicians.

---

## 6. Branches, Schedules & Automatic Assignment

Full design record: `docs/BRANCHES_AUTO_ASSIGNMENT.md`.

- **Branches** (`branches`: name, priority, is_active) serve **cities** (`cities`: name unique, `branch_id`); `technicians.branch_id` is nullable for legacy rows, and only branched technicians are auto-assignable.
- **Weekly schedules** (`technician_schedules`: `day_of_week` 0–6, `is_working`, `start_time`/`end_time`); every new technician is seeded the default week (Fri off, Sat 10:00–18:00, else 08:00–17:00), editable on `admin/technicians/{technician}/schedule/edit`.
- **`TechnicianAssignmentService::autoAssign()`** picks by branch → schedule coverage → daily load (< 2, BR-011) → no overlap → fairness ordering, committing under row locks; failures return a reason instead of throwing.
- Approving a request triggers `autoAssign()`; unmatched requests stay approved/unassigned (visible under Unassigned Jobs) with the reason flashed.

---

## 7. Branch Managers

- Managers are one role: assignment decides scope. A manager running an
  active branch is confined to `/branch/*` (`role:manager` plus the
  `branch.scope` middleware, which redirects assigned managers out of the
  global admin area); unassigned managers keep the legacy global staff
  access, and admins always pass through. `User::isBranchScoped()` is the
  single predicate behind the middleware, the dashboard landing, and the
  sidebar visibility.
- `branches.manager_user_id` (nullable unique) links at most one active manager per branch; `BranchService::assignManager()`/`createManager()` enforce account eligibility and the one-branch-per-manager rule for Super Admins.
- Managers see only their branch: dashboard overview, technicians, schedules, and requests (review/assign/unassign with auto-assign on approval). Every branch controller resolves the manager's active branch and 404s foreign records.
- Managers see only their branch: dashboard overview, technicians, schedules, and requests (review/assign/unassign with auto-assign on approval). Every branch controller resolves the manager's active branch and 404s foreign records.
- Full rules in `docs/BRANCHES_AUTO_ASSIGNMENT.md` §8.
