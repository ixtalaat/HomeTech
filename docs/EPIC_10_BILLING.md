# Epic 10 — Billing

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 10 (Billing)** in HomeTech.

---

## 1. Pricing (10.1) & Discounts (10.2)

`App\Services\PricingService` separates estimation from actuals:

- `breakdown($workOrder)` — `service_base` (estimate anchor), `labor`, `materials` (frozen costs), `additional` (billable only), `subtotal`. Components persist separately on the invoice row.
- `discountAmount($subtotal, $type, $value, $actor)` — `fixed` (EGP) or `percent` (of subtotal, ≤100%); refuses negative values and any discount that would drive the total below zero; discounts above threshold require a manager (`percent > 20%` or `fixed > 500 EGP`, per `config/billing.php`), otherwise `BillingException`.
- Discount applications rewrite invoice totals and write `AuditLog` rows (PRD §27).

---

## 2. Domain Entities & Database Schema

### 2.1 Invoices (`invoices`)

| Column | Type | Constraints | Description |
|---|---|---|---|
| `number` | `varchar(255)` | Unique | `INV-00001` sequence from the row id (gapless, collision-free) |
| `maintenance_request_id` | `bigint unsigned` | Unique, FK (`cascadeOnDelete`) | One invoice per request |
| `user_id` | `bigint unsigned` | FK (`restrictOnDelete`) | Billed customer |
| `subtotal` / `discount_*` / `total` / `paid_amount` | `decimal(10,2)` | — | Tracked components |
| `discount_type` | `varchar(20)` | Nullable, `DiscountType` enum | `fixed, percent` |
| `status` | `varchar(30)` | Default `draft`, `InvoiceStatus` enum | `draft, issued, partially_paid, paid, cancelled` |
| `issued_at` / `created_by` / `notes` | — | — | Issue timestamp, creator, notes |

### 2.2 Invoice Items (`invoice_items`, BR-010)

| Column | Type | Constraints | Description |
|---|---|---|---|
| `invoice_id` | `bigint unsigned` | FK (`cascadeOnDelete`) | Parent invoice |
| `item_type` | `varchar(30)` | `InvoiceItemType` enum | `service, labor, material, additional` |
| `description` / `quantity` / `unit_price` / `total` | — | Snapshots | Frozen at generation |
| `source_type` / `source_id` | nullable morph | — | Link to the source charge (`Service, LaborItem, MaterialUsage, AdditionalWork`) |

Generation iterates actuals only (service base, each labor row, each usage row, `billableFor()` extras) — rejected/pending extras are structurally unreachable.

### 2.3 Payments (`payments`, BR-006)

| Column | Type | Constraints | Description |
|---|---|---|---|
| `invoice_id` | `bigint unsigned` | FK (`cascadeOnDelete`) | Parent invoice |
| `amount` | `decimal(10,2)` | Positive | Payment amount |
| `method` | `varchar(30)` | `PaymentMethod` enum | `cash, card, bank_transfer` |
| `reference` / `received_by` / `paid_at` / `notes` | — | — | Reference, receiver, timestamp, notes |

No delete route exists — payment records are never silently deleted (PRD §20).

### 2.4 Cancellations (`cancellations`, BR-008, PRD §21 entity)

| Column | Type | Constraints | Description |
|---|---|---|---|
| `maintenance_request_id` | `bigint unsigned` | Unique, FK (`cascadeOnDelete`) | Cancelled request |
| `reason` | `text` | Required | Cancellation reason |
| `fee` | `decimal(10,2)` | Policy-computed | 0 or % of base estimate |
| `cancelled_by` | `bigint unsigned` | Nullable FK | Actor |

Policy constants live in `config/billing.php` (`free_cancellation_hours: 24`, `late_cancellation_fee_percent: 10`).

---

## 3. Services & Flows

- **`InvoiceService`** — `generate()` (completed job, no existing invoice → draft + snapshot lines + audit), `issue()` (draft → issued, request → `invoiced`), `applyDiscount()` (paid/cancelled immutable), `cancelInvoice()` (only unpaid, zero paid), `markClosed()` (paid → request `closed`).
- **`PaymentService::pay()`** — only `issued/partially_paid` invoices; `0 < amount ≤ remaining` (BR-006, over-payment refused quoting the remainder); one transaction records the payment, bumps `paid_amount`, flips `partially_paid/paid`, and moves the request to `paid` on settlement.
- **`CancellationService::cancel()`** — refused past visit-start (`in_progress` and beyond) and on duplicates; fee 0 beyond the free window (or with no appointment), else % of base estimate; cancels the booked appointment too; request → `cancelled`.
- Unlocked edges: `completed → invoiced`, `invoiced → paid`, `paid → closed`, `scheduled → cancelled`.

---

## 4. Business Rules & Security

1. **Invoice integrity (BR-010)**, **payment limit (BR-006)**, **cancellation policy (BR-008)** — all enforced in services with exact messages; UI pre-filters but never replaces them.
2. **Staff-only billing management** (`InvoicePolicy::manage` + admin group); customers view/pay only their own invoices (ownership 404s); paid/cancelled invoices immutable through normal ops.
3. **Customer cancellation** from the request page (reason required, fee previewed in the confirmation message); staff cancellation from the admin request page.

---

## 5. Endpoints & Route Map

### Admin (`admin.` prefix):
- `GET /admin/invoices` + `GET /admin/invoices/{invoice}` — List (status/search filters) and detail (lines, totals, payments, discount/payment forms, issue/cancel/close).
- `POST /admin/invoices/work-orders/{workOrder}/generate`, `PATCH .../{invoice}/issue|discount|cancel|close`, `POST .../{invoice}/payments`.
- `PATCH /admin/requests/{maintenanceRequest}/cancel` — Staff cancellation.

### Customer:
- `GET /invoices`, `GET /invoices/{invoice}`, `POST /invoices/{invoice}/pay` — List, detail, self-pay (full/partial).
- `POST /requests/{maintenanceRequest}/cancel` — Self-cancel with reason.

---

## 6. Test Coverage

- `tests/Feature/BillingTest.php` — Breakdown math; fixed/percent discounts; negative-total, >100%, and over-threshold-admin refusals; manager override; draft generation (4 typed lines incl. rejected-extra absence, every line with a live source, `INV-` number, duplicate refused); draft payment refusal; issue flow; partial→paid transitions with request flip; over-payment and post-paid refusals; owner self-pay; close/cancel rules; non-staff 403s.
- `tests/Feature/CancellationTest.php` — Free cancel without appointment; >24h free vs <24h 10%-of-base fee; in-progress and duplicate refusals; owner HTTP cancel; foreign cancel 404s.
