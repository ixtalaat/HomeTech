# Epic 4 — Maintenance Requests

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 4 (Maintenance Requests)** in HomeTech.

---

## 1. Domain Entities & Database Schema

### 1.1 Maintenance Requests (`maintenance_requests`)
Represents a customer-submitted maintenance job request (PRD §8).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Request ID |
| `user_id` | `bigint unsigned` | FK -> `users.id` (`cascadeOnDelete`) | Owning customer |
| `service_id` | `bigint unsigned` | FK -> `services.id` (`restrictOnDelete`) | Requested service |
| `address_id` | `bigint unsigned` | FK -> `addresses.id` (`restrictOnDelete`) | Service address |
| `description` | `text` | Not Null | Problem description |
| `preferred_date` | `date` | Not Null | Preferred appointment date (must be future) |
| `preferred_time` | `time` | Not Null | Preferred appointment time |
| `photos` | `json` | Nullable | Stored photo paths on the `public` disk |
| `status` | `varchar(50)` | Index, Default `'pending_review'` | Current `RequestStatus` value |
| `rejection_reason` | `text` | Nullable | Required when rejected |
| `admin_note` | `text` | Nullable | Staff note (approval / info request) |
| `reviewed_by` | `bigint unsigned` | Nullable, FK -> `users.id` (`nullOnDelete`) | Reviewing staff member |
| `reviewed_at` | `timestamp` | Nullable | Review timestamp |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

**Indexes**:
- `maintenance_requests_user_id_status_index` (`user_id`, `status`)
- `maintenance_requests_status_index` (`status`)

### 1.2 Status Histories (`maintenance_request_status_histories`)
Append-only timeline powering customer/admin history views and auditability (PRD §27).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique History ID |
| `maintenance_request_id` | `bigint unsigned` | FK -> `maintenance_requests.id` (`cascadeOnDelete`, short key `mrsh_request_fk`) | Parent request |
| `from_status` | `varchar(50)` | Nullable | Previous status (`null` on creation) |
| `status` | `varchar(50)` | Not Null | New status |
| `changed_by` | `bigint unsigned` | Nullable, FK -> `users.id` (`nullOnDelete`) | Actor |
| `reason` | `text` | Nullable | Transition reason / note |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

> MySQL identifier limit (64 chars): the FK uses the explicit short name `mrsh_request_fk` and the index `mrsh_request_idx` — auto-generated names exceed the limit for this table.

### 1.3 Related schema change
- `addresses` are now protected: `AddressController@destroy` refuses deletion when `maintenanceRequests()->exists()` (friendly error), backed by `restrictOnDelete` on `address_id`. This preserves job history.

---

## 2. Statuses & Transitions

### 2.1 `RequestStatus` Enum (`App\Enums\RequestStatus`)
Backed string enum covering the full PRD §9 workflow plus review states:

`pending_review`, `info_requested`, `approved`, `rejected`, `technician_assigned`, `scheduled`, `technician_on_way`, `in_progress`, `waiting_customer_approval`, `completed`, `invoiced`, `paid`, `closed`, `cancelled`

- `label()` returns the human-readable name (e.g. "Technician On The Way").
- `isTerminal()` is true for `rejected`, `closed`, `cancelled`.

### 2.2 Transition Service (`App\Services\RequestStatusService`)
Single choke point for all status changes (mirrors the `AddressService` pattern):

- `canTransition($from, $to): bool` — checked against an explicit `TRANSITIONS` map.
- `transition($request, $to, $actor, $reason)` — runs in a `DB::transaction`, updates status, appends a history row; throws `InvalidStatusTransitionException` (surfaced as a 422/`error` flash) on illegal moves.
- Epic 4 unlocks only review edges: `pending_review → {approved, rejected, info_requested, cancelled}`, `info_requested → {pending_review, rejected, cancelled}`. Assignment/scheduling/work/billing edges are declared in the enum but stay locked until Epics 5–10 implement them.
- **Rejected is terminal**: no outgoing edges, so a rejected request can never be assigned (PRD §10).

---

## 3. Business Rules & Security

1. **Active service (PRD §10)**: `service_id` must exist with `is_active = true` AND belong to an active category (closure rule in `StoreMaintenanceRequestRequest`).
2. **Address ownership (PRD §7)**: `address_id` must pass `Rule::exists('addresses','id')->where('user_id', auth()->id())` — customers cannot use another customer's address.
3. **Future appointment (PRD §10)**: `preferred_date` must be `after:today`.
4. **Pending Review on creation (PRD §8)**: `status` is forced server-side; never taken from user input.
5. **Photo uploads**: `photos.*` must be `image|mimes:jpg,jpeg,png,webp|max:2048`, max 5 files; stored as `request-photos/{id}/{hash}` on the `public` disk with hashed filenames (client names never trusted).
6. **Ownership opacity**: foreign request IDs return `404` (scoped `abort_unless(...isOwnedBy...)` before policy checks), so IDs cannot be probed.
7. **Staff-only review**: `MaintenanceRequestPolicy::review/updateAppointment` require `admin/manager` role AND a reviewable status; registered via `Gate::policy()` in `AppServiceProvider` (same as `CustomerPolicy` — Laravel cannot auto-discover it).
8. **Customers cannot edit submitted requests**; corrections go through the admin `request-info` round-trip.

---

## 4. Endpoints & Route Map

### Customer Routes (`auth`):
- `GET /requests` (`requests.index`) — Own requests with status filter.
- `GET /requests/create` (`requests.create`) — Form with active services + own addresses; accepts `?service_id=` preselect (service catalog "Request This Service Now" CTA links here).
- `POST /requests` (`requests.store`) — Validated creation + photo upload + initial history row.
- `GET /requests/{maintenanceRequest}` (`requests.show`) — Details, photos, admin notes, status timeline.

### Admin / Manager Routes (`auth` + `role:admin,manager`, `admin.` prefix):
- `GET /admin/requests` (`admin.requests.index`) — List with status filter + customer/description search.
- `GET /admin/requests/{maintenanceRequest}` (`admin.requests.show`) — Review page: details, photos, appointment editor, review actions, history.
- `PATCH /admin/requests/{maintenanceRequest}/approve` — Approve (+ optional note/appointment change), then attempts automatic technician assignment: success flashes the assignee, otherwise the request stays approved/unassigned with the reason flashed (see `docs/BRANCHES_AUTO_ASSIGNMENT.md`).
- `PATCH /admin/requests/{maintenanceRequest}/reject` — Reject (reason required).
- `PATCH /admin/requests/{maintenanceRequest}/request-info` — Move to `info_requested` (note required).
- `PATCH /admin/requests/{maintenanceRequest}/appointment` — Change preferred date/time (future-only, reviewable requests only).

---

## 5. Test Coverage

Pest feature tests:
- `tests/Feature/MaintenanceRequestTest.php`
  - Happy path: creates `pending_review` request with uploaded photo (`Storage::fake`) and initial history row.
  - Rejects inactive services / inactive-category services.
  - Rejects foreign addresses; rejects past dates; blocks cross-customer `show` (404); guest redirects.
  - Photo helper builds a real 1×1 PNG in-code (no GD extension required).
- `tests/Feature/Admin/MaintenanceRequestReviewTest.php`
  - Non-staff forbidden from review routes; approve/reject/info/appointment flows; rejection reason required; review actions on already-reviewed requests forbidden.
- `tests/Feature/RequestStatusTransitionTest.php`
  - Illegal transitions throw (`pending→completed`, `rejected→approved`, `approved→completed`, …) and leave status unchanged; legal transitions persist + record history with actor/reason; rejected requests report `canTransition(→technician_assigned) === false`.
