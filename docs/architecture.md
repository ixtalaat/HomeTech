# HomeTech — Architecture

How the codebase is organized, where logic lives, and the conventions every change must follow. Read this before adding a feature; per-epic detail lives in `docs/EPIC_*.md`.

## 1. Layering

```
HTTP (routes → FormRequests → controllers → Blade)
        │ authorize · validate · delegate · respond — no domain logic
        ▼
Services (app/Services) — the only place business rules live
        │ transactions · guards · transitions · notifications
        ▼
Models (app/Models) — relations, scopes, casts, tiny predicates
```

Controllers never query-filter, never transact, never compute money, never send notifications. If a controller action needs more than authorize + validated-data + one service call + redirect/view, the excess belongs in a service. FormRequests own validation **and** the first authorization gate; policies own the rest.

## 2. Request Lifecycle (the happy path)

```
register/login (Auth) ──► profile, addresses
services (public catalog) ──► requests.store → pending_review
admin.requests.{approve} → approved
admin.requests.assign → technician_assigned + appointment auto-booked → scheduled
technician.jobs.on-way → technician_on_way ──► start → work order in_progress
additional-work request → waiting_customer_approval → decide → in_progress
jobs.complete → completed (+ appointment completed)
invoices.generate → draft → issue → invoiced
invoices.pay → partially_paid → paid (request paid) → close → closed
requests.reviews.store (owner, finished job only)
```

Every arrow is a `RequestStatusService::transition()` call: one choke point, illegal moves throw `InvalidStatusTransitionException`, every move appends a `maintenance_request_status_histories` row. New workflow edges are unlocked per epic in that service's `TRANSITIONS` map — never bypassed.

## 3. Service Catalog

| Service | Owns |
|---|---|
| `RequestStatusService` | Transition map, history writes |
| `MaintenanceRequestService` | Customer/admin listing, creation + photos + initial history |
| `RequestReviewService` | approve / reject / request-info / appointment edits |
| `TechnicianAssignmentService` | BR-001 guards, assign / reassign / unassign, `eligibleFor()` |
| `SchedulingService` | BR-002 overlap detection, book / reschedule / cancel / start-guard / on-way |
| `WorkOrderService` | Visit lifecycle, diagnosis/notes/labor/photos/materials recording, BR-007 lock, staff corrections |
| `AdditionalWorkService` | BR-005 request/decide/perform, `billableFor()` contract for billing |
| `InventoryService` | BR-003/004 transactional stock, movements ledger, adjustments, reversals |
| `PricingService` | Cost breakdown, discount math + manager gate |
| `InvoiceService` | Generation (BR-010), issue, discounts + approval queue, cancel, close |
| `PaymentService` | BR-006 guarded payments, confirmation workflow, status following |
| `CancellationService` | BR-008 policy, fees, auto fee-invoicing |
| `ReviewService` | BR-009 submission guards |
| `StripeService` | Checkout sessions, idempotent settlement |
| `PhoneVerificationService` + `WhatsAppService` | OTP codes (hashed, expiring, attempt-capped), log/twilio/meta drivers |
| `ReportingService` | Dashboard + revenue/job/technician/inventory aggregates |
| `CustomerService`, `TechnicianService`, `CatalogService` | Admin CRUD, filters, skill sync |

Cross-service calls go through public methods only (e.g. assignment calls scheduling; cancellation calls invoicing). Services never touch HTTP.

## 4. Cross-Cutting Rules

- **Money**: stored as `decimal(10,2)`; computed in services with `round(..., 2)` at each step (binary-float dust is a test failure, not a rounding strategy). Frozen snapshots (`unit_cost`, line items) at record time — live prices never rewrite history.
- **Ownership opacity**: foreign IDs return 404 (scoped `abort_unless` before policy checks). `user_id`-style fields are never taken from request input.
- **Authorization**: `role:*` middleware for areas + policies per model, explicitly mapped in `AppServiceProvider::boot()` (Laravel cannot auto-discover our policy names). State-dependent abilities live in FormRequests/policies; invariant guards live in services and throw domain exceptions that controllers convert to `error` flashes.
- **Auditability**: `statusHistories` narrate workflow; `audit_logs` record financial/inventory/correction/decision events with actor + diff + reason. The movements ledger must always reconcile (`SUM(quantity) == current_stock`, tested).
- **Notifications**: database channel (queueable; worker runs under `composer run dev`, `sync` driver in tests). Email content exists for invoice/payment events — flows automatically once `MAIL_MAILER` is real.
- **Uploads**: private `local` disk, hashed names, served only through `FileController` ownership checks. Tests use GD-free embedded fixtures and disk fakes.

## 5. Frontend

Blade + Tailwind v4 (CSS-first `@theme`), DM Sans / Plus Jakarta Sans, teal-slate system. Shared vocabulary in `resources/css/app.css`: `.primary-button/.secondary-button/.danger-button` (44px targets), `.form-label/.form-input`, `.card`, `.badge-*` + `<x-status-badge>` (semantic color per status, never gray-on-gray meaning), `.timeline` stepper, `.empty-state`, `.detail-label/.detail-value`. Motion: 180ms transitions, `prefers-reduced-motion` kill-switch, visible focus rings, skip link, mobile bottom nav, aria-labeled icon buttons.

## 6. Testing & Workflow

- Pest, `tests/Feature/` per epic + `tests/Pest.php` shared fixtures (`skilledTechnician`, `scheduledRequest`, `inProgressWorkOrder`, `completedWorkOrderWithCharges`, `fakePngPhoto`) — helpers live there so single-file runs work.
- MySQL for tests (`hometech_testing`), `RefreshDatabase`, `Notification::fake()` for event tests, container-bound fakes for external clients (Stripe) so the suite is network-proof.
- `vendor/bin/pint --dirty` before every commit; conventional commits (`feat/fix/docs/refactor`); EPIC docs updated with the code, not after.
