# Epic 11 — Reviews

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 11 (Reviews)** in HomeTech.

---

## 1. Domain Entities & Database Schema

### 1.1 Reviews (`reviews`)
Post-job customer feedback (PRD §23).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Review ID |
| `maintenance_request_id` | `bigint unsigned` | Unique, FK -> `maintenance_requests.id` (`cascadeOnDelete`) | Reviewed job (one review per job) |
| `user_id` | `bigint unsigned` | FK -> `users.id` (`restrictOnDelete`) | Reviewing customer (authorship survives) |
| `rating` | `unsigned tiny int` | 1–5 | Star rating |
| `comment` | `text` | Nullable | Written feedback |
| `created_at`, `updated_at` | `timestamp` | Nullable | Submission date |

`Review` model: `request()/user()` relations, `scopeHighlyRated()` (4★+, spare for Epic 12 aggregates), `MaintenanceRequest::review(): HasOne` added.

---

## 2. Submission Rules (BR-009)

`App\Services\ReviewService::submit()` enforces, in order:

1. Actor owns the job — staff can never submit.
2. Actor is a customer.
3. Job is finished: status in `completed, invoiced, paid, closed` (reviewable as soon as work is done, not only after payment).
4. Rating within 1–5 (also validated in the FormRequest).
5. No existing review (service guard + unique constraint backstop).

No status edges move — reviews attach without changing the job. No edits after submission (contact support for changes).

---

## 3. Endpoints & Route Map

### Customer:
- `POST /requests/{maintenanceRequest}/reviews` (`requests.reviews.store`) — Submit (`rating 1–5`, optional `comment ≤ 2000`). Non-owners 404; unfinished jobs and duplicates surface `error` flashes.

### Admin (`admin.` prefix):
- `GET /admin/reviews` (`admin.reviews.index`) — List with rating filter.

### Surfaces
- Customer request page: star display for submitted reviews; submission form only when eligible (finished + unreviewed + owner); hint text otherwise.
- Admin request page: read-only star display.

---

## 4. Test Coverage

`tests/Feature/ReviewTest.php`:
- Owner reviews a completed job (row, rating, authorship).
- Non-owner and technician submits 404; service-level foreign submit throws; nothing persisted (BR-009).
- Unfinished (`scheduled`) job refused; second review refused.
- Rating boundaries: 0/6 rejected by validation, 1/5 accepted.
- Staff index with rating filter (sees matching, hides others); customer forbidden from admin list.
