# Epic 8 — Inventory

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 8 (Inventory)** in HomeTech.

---

## 1. Domain Entities & Database Schema

### 1.1 Inventory Items (`inventory_items`)
Materials the company stocks for maintenance jobs (PRD §15).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Item ID |
| `name` | `varchar(255)` | Unique, Not Null | Material name (e.g. "Capacitor") |
| `sku` | `varchar(255)` | Nullable, Unique | Stock-keeping unit |
| `unit` | `varchar(20)` | Default `'pcs'` | Unit of measure |
| `current_stock` | `int` | Default `0` | Live stock counter (see §2) |
| `low_stock_threshold` | `unsigned int` | Default `5` | Low-stock flag level |
| `unit_cost` | `decimal(10,2)` | Default `0` | Current unit cost in EGP |
| `notes` | `text` | Nullable | Internal notes |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

No `is_active` flag by design — zero stock already means unavailable.

### 1.2 Inventory Movements (`inventory_movements`)
Append-only ledger; every stock change writes exactly one row (PRD §15, BR-004).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Movement ID |
| `inventory_item_id` | `bigint unsigned` | FK -> `inventory_items.id` (`cascadeOnDelete`) | Item |
| `quantity` | `int` | **Signed** | Consumption negative, everything else positive |
| `type` | `varchar(30)` | `MovementType` enum | `purchase, adjustment, consumption, return, reversal` |
| `work_order_id` | `bigint unsigned` | Nullable, FK (`nullOnDelete`) | Linked job (consumption) |
| `technician_id` | `bigint unsigned` | Nullable, FK (`nullOnDelete`) | Technician responsible |
| `created_by` | `bigint unsigned` | Nullable, FK -> `users.id` | Actor |
| `notes` | `text` | Nullable | Notes / reversal reference |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

Because quantities are signed, `SUM(quantity)` must always equal `current_stock` — enforced by test (`ledgerBalance()`).

### 1.3 Material Usage (`material_usage`)
Job-level consumption records with frozen pricing for Epic 10 invoicing.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Usage ID |
| `work_order_id` | `bigint unsigned` | FK -> `work_orders.id` (`cascadeOnDelete`) | Job |
| `inventory_item_id` | `bigint unsigned` | FK -> `inventory_items.id` (`restrictOnDelete`) | History survives item deletion |
| `quantity` | `unsigned int` | Positive | Units used |
| `unit_cost` | `decimal(10,2)` | **Snapshot** of item cost at use time | Frozen for invoicing |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

---

## 2. Stock Service (BR-003, BR-004)

`App\Services\InventoryService` is the single choke point; every method runs in a transaction with `lockForUpdate` on the item row (race-safe):

- `recordUsage($workOrder, $item, $qty, $actor)` — creates the usage row (frozen cost) **and** the linked negative `consumption` movement atomically: never one without the other (BR-004).
- `consume` path rejects zero/negative quantities and throws `InsufficientStockException` when `stock < qty`, leaving stock **and** movement count untouched (BR-003).
- `purchase()/returnStock()` — increase paths with typed movements.
- `adjust($delta)` — manual corrections (either sign, never below zero) + an `AuditLog` row with actor and reason.
- `reverse($movement)` — offsetting `reversal` entry referencing the original; the ledger is never edited.
- Item creation writes the opening stock as a `purchase` movement so the ledger balances from day one; item `update` can never touch `current_stock` directly (stock moves only via movements); deletion is blocked with any movement/usage history.

`WorkOrder::recordMaterialUsage()` delegates to the service after the BR-007 edit guard; `materialsTotal()` sums frozen `quantity × unit_cost` for Epic 10.

---

## 3. Business Rules & Security

1. **No negative stock (BR-003)** and **traceable consumption (BR-004)** enforced in the service, re-checked server-side regardless of client-side caps.
2. **Staff-only inventory area** (`InventoryPolicy` + `Gate::policy()` mapping + `role:admin,manager` middleware); technicians only consume via their own open jobs (existing work-order ownership + BR-007 lock).
3. **Low-stock monitoring**: per-item threshold, `scopeLowStock()` (`stock <= threshold`, boundary-inclusive), alert panel + filter on the inventory index — the same query feeds the Epic 12 report.

---

## 4. Endpoints & Route Map

### Admin (`admin.` prefix):
- `Route::resource('inventory', ...)` — index (search + low-stock filter + alert panel), create/store, show (ledger), edit/update, destroy (history-guarded).
- `PATCH /admin/inventory/{inventoryItem}/adjust` — Manual adjustment (`delta ≠ 0`, `reason` required; never below zero).

### Technician (`technician.` prefix):
- `POST /technician/jobs/{workOrder}/materials` — Record usage (`inventory_item_id`, `quantity ≥ 1`); stock drops immediately, errors surface as flashes.

The technician job page gained a Materials section (usage list + frozen-cost totals + use form limited to in-stock items).

---

## 5. Test Coverage

`tests/Feature/InventoryTest.php`:
- Usage creates a 1:1 usage-row/`consumption` pair with frozen cost, linked work order + technician; stock decrements.
- Over-consumption throws with stock, movement, and usage counts all unchanged (BR-003 atomicity asserted on all three).
- Ledger reconciliation (`purchase 10 − use 3 + return 1 = 8 = ledger sum`); reversal restores stock via a typed row without editing history.
- Low-stock boundary (`stock == threshold` counts as low) and query correctness.
- Technician HTTP happy path (totals: 2 × 50.00 = 100.00), over-stock blocked, completed-job usage forbidden (BR-007).
- Admin CRUD incl. opening purchase movement, adjustment floor, history-guarded delete, non-staff 403s.
