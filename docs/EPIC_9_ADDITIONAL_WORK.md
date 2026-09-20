# Epic 9 — Additional Work

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 9 (Additional Work)** in HomeTech.

---

## 1. Domain Entities & Database Schema

### 1.1 Additional Work (`additional_work`)
Technician-discovered extras requiring customer approval before billing (PRD §16, BR-005).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Item ID |
| `work_order_id` | `bigint unsigned` | FK -> `work_orders.id` (`cascadeOnDelete`) | Parent job |
| `description` | `text` | Not Null | Extra work description |
| `cost` | `decimal(10,2)` | Default `0` | Additional cost in EGP |
| `status` | `varchar(30)` | Index, Default `'pending_approval'` | Current `AdditionalWorkStatus` value |
| `requested_by` | `bigint unsigned` | FK -> `users.id` (`restrictOnDelete`) | Requesting technician |
| `decided_by` | `bigint unsigned` | Nullable, FK -> `users.id` | Deciding customer |
| `decided_at` | `timestamp` | Nullable | Decision timestamp (approval proof) |
| `completed_at` | `timestamp` | Nullable | Performed timestamp |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

### 1.2 `AdditionalWorkStatus` Enum
`pending_approval`, `approved`, `rejected`, `completed` + `label()`. `scopeBillable()` = approved + completed — the only states Epic 10 may invoice.

### 1.3 Notifications (database channel, PRD §24 MVP)
- `AdditionalWorkRequiresApproval` → customer on every request (request link, description, cost).
- `AdditionalWorkDecided` → technician on approve/reject.
- Uses the `notifications` table migration. No inbox bell yet (deferred to Epic 12); the actionable surfaces are the customer banner and technician page.

---

## 2. Approval Flow

`App\Services\AdditionalWorkService` is the single choke point:

- `request($workOrder, $tech, $description, $cost)` — only on `in_progress` orders (BR-007 reuse: completed orders refused); creates the `pending_approval` row + transitions the parent request `in_progress → waiting_customer_approval` + notifies the customer, atomically.
- `decide($item, $customer, $approve)` — only pending items, only the owning customer (technician self-approval explicitly refused); stamps `decided_by/at`; returns the request to `in_progress` on **both** outcomes (job continues; rejected extras simply don't bill).
- `markCompleted($item, $tech)` — only `approved` items, only the assigned technician; records the performed checkpoint Epic 10 invoices against.
- `billableFor($workOrder)` — the Epic 10 contract: pending/rejected items are excluded by construction (BR-005).
- Unlocked edges: `in_progress → waiting_customer_approval`, `waiting_customer_approval → in_progress`.

The Epic 7 completion guard now also blocks while any additional work is still pending.

---

## 3. Business Rules & Security

1. **No charge without approval (BR-005)**: billability derives from status, never from a flag the technician could set.
2. **Decision integrity**: owner-only (404 otherwise), pending-only (double decisions refused), timestamped with actor.
3. **Surfaces**: pending-extras banner with Approve/Reject on the customer request page; technician job page lists extras with statuses, request form, and "mark performed" for approved items.

---

## 4. Endpoints & Route Map

### Customer (`auth`):
- `PATCH /additional-work/{additionalWork}/approve` (`additional-work.approve`) — Approve (owner + pending only).
- `PATCH /additional-work/{additionalWork}/reject` (`additional-work.reject`) — Reject (never billed).

### Technician (`technician.` prefix):
- `POST /technician/jobs/{workOrder}/additional-work` (`technician.jobs.additional-work`) — Request approval.
- `PATCH /technician/jobs/additional-work/{additionalWork}/complete` (`technician.jobs.additional-work.complete`) — Mark performed.

---

## 5. Test Coverage

`tests/Feature/AdditionalWorkTest.php`:
- Request creates a `pending_approval` item, flips the request to `waiting_customer_approval`, and writes the customer notification.
- Approve stamps `decided_by/at`, returns the request to `in_progress`, item present in `billableFor()`; reject returns likewise with the item **absent** (BR-005).
- Pending extras block work-order completion.
- Self-approval, foreign-customer decisions (service + HTTP 403), double decisions, and creation on completed orders all refused.
- `markCompleted` works for approved items, refuses rejected ones, and refuses repeats.
