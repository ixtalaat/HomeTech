# HomeTech — Home Maintenance Service Management Platform

HomeTech manages the complete lifecycle of a home maintenance job: customer request → admin review → technician assignment → scheduling → work order (diagnosis/labor/materials) → additional-work approval → invoicing → payment → closure → review.

**Stack:** PHP 8.2 · Laravel 12 · MySQL · Blade + Tailwind · Pest (147 tests, 583 assertions)

## Quickstart

```bash
cp .env.example .env
# set DB_* and ADMIN_PASSWORD / MANAGER_PASSWORD
composer install && npm install
php artisan key:generate
php artisan migrate --fresh --seed
composer run dev   # app + queue worker + vite
```

Open http://localhost:8000.

## Demo Accounts (after seeding)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@hometech.com` | `ADMIN_PASSWORD` from `.env` |
| Manager | `manager@hometech.com` | `MANAGER_PASSWORD` from `.env` |
| Technician (Plumbing) | `ahmed@hometech.com` | `password` (or `TECHNICIAN_PASSWORD`) |
| Technician (Electrical/AC) | `sara@hometech.com` | `password` |
| Technician (Painting/Appliance) | `omar@hometech.com` | `password` |
| Customer | `demo@hometech.com` / `test@example.com` | `password` |

The demo customer ships with a Cairo address and a pending AC request, and inventory comes stocked (including one low-stock item to see the alert).

## 5-Minute Tour (the PRD §38 scenario)

1. **Customer** (`demo@…`): open the pending request → Services → request an AC repair with a photo.
2. **Admin**: Requests → approve → assign Ahmed (slot auto-books from the preferred time).
3. **Ahmed**: My Jobs → Start Visit → diagnosis + labor + use 1× Capacitor → request extra work (100 EGP connector).
4. **Customer**: approve the extra from the request banner.
5. **Ahmed**: mark it performed → Complete Job.
6. **Admin**: generate + issue the invoice → record a partial cash payment.
7. **Customer**: My invoices → settle by card → leave a 5★ review.
8. **Admin**: close the invoice → Dashboard → revenue report.

## Architecture

Thin controllers (HTTP only: authorize → validate → delegate → respond); all domain logic lives in `app/Services/`:

- `RequestStatusService` — one guarded transition map for the whole job lifecycle; illegal moves throw.
- `TechnicianAssignmentService` (BR-001), `SchedulingService` (BR-002 overlap detection), `WorkOrderService` (BR-007 lock), `InventoryService` (BR-003/004, row-locked), `AdditionalWorkService` (BR-005), `InvoiceService` / `PaymentService` (BR-006/010), `CancellationService` (BR-008), `PricingService`, `ReviewService`, `ReportingService`, plus `CustomerService`, `TechnicianService`, `CatalogService`, `MaintenanceRequestService`, `RequestReviewService`.
- Every state change writes a `maintenance_request_status_histories` row; financial/inventory/correction events write `audit_logs`.

## Business Rules Enforced Server-Side

| Code | Rule |
|---|---|
| BR-001 | Technicians only get jobs in categories they are skilled for |
| BR-002 | No overlapping appointments per technician (boundary-touching allowed) |
| BR-003 | Stock can never go negative (transactional, race-safe) |
| BR-004 | Every consumption writes an inventory movement |
| BR-005 | Additional work bills only after customer approval |
| BR-006 | Payments can't exceed the remaining balance |
| BR-007 | Completed jobs locked; staff corrections are logged |
| BR-008 | Configurable cancellation fees (`config/billing.php`) |
| BR-009 | One review per finished job, owner only |
| BR-010 | Invoice lines always reference their source charges |
| BR-011/012 | Role policies + gates everywhere; audit log for sensitive actions |

## Testing

```bash
php artisan test --compact   # full suite
php artisan test --compact tests/Feature/BillingTest.php
vendor/bin/pint --dirty      # format before committing
```

Conventions: Pest feature tests per epic under `tests/Feature/`, shared fixtures (`skilledTechnician`, `scheduledRequest`, `inProgressWorkOrder`, …) in `tests/Pest.php`, GD-free image fixtures.

## Docs

- `docs/prd.md` — product requirements
- `docs/EPIC_*.md` — per-epic architecture notes (1–12)
- `config/billing.php` — discount gate + cancellation policy tunables
