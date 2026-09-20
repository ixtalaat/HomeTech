# Epic 3 — Customers

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 3 (Customers)** in HomeTech.

---

## 1. Domain Entities & Database Schema

### 1.1 Users — Customer Profile Fields (`users`)
Epic 1 created the `users` table (name, phone, email, password, `role`). Epic 3 adds the customer-specific account status field from PRD §7.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `is_active` | `tinyint(1)` | Index, Default `1` | Account status (active/suspended) |

Conventions on the `User` model:
- `is_active` is `fillable` and cast to `boolean` (same pattern as `services.is_active`).
- `addresses(): HasMany` and `defaultAddress(): HasOne->where('is_default', true)`.
- `maintenanceRequests(): HasMany` (added with Epic 4).
- `scopeCustomers()` filters `role = customer`; `scopeActive()` filters `is_active`.
- `ownsAddress(Address)` helper for ownership checks.

### 1.2 Addresses (`addresses`)
Customers can maintain multiple addresses (PRD §7). Each maintenance request must reference an address belonging to the customer.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Address ID |
| `user_id` | `bigint unsigned` | FK -> `users.id` (`cascadeOnDelete`) | Owning customer |
| `title` | `varchar(100)` | Not Null | Address title (e.g. "Home", "Office") |
| `street` | `varchar(255)` | Not Null | Building/street information |
| `city` | `varchar(100)` | Not Null | City |
| `notes` | `text` | Nullable | Additional notes |
| `is_default` | `tinyint(1)` | Index, Default `0` | Default address flag |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

**Indexes**:
- `addresses_user_id_is_default_index` (`user_id`, `is_default`)

`Address` model conventions (mirror `Service`):
- `fillable`: `user_id, title, street, city, notes, is_default`; `is_default` cast to `boolean`.
- `user(): BelongsTo`, `maintenanceRequests(): HasMany` (Epic 4).
- `scopeOwnedBy($userId)`, `scopeDefault()`, `isOwnedBy(User)` helper.

### 1.3 Default-Address Invariant
Exactly one default address per customer, enforced transactionally in `App\Services\AddressService` (mirrors the domain-service pattern):
- `createForUser()` — the first address is always forced to default; creating with `is_default=true` unsets the others.
- `update()` — setting default unsets the others; unsetting the only default without a replacement is ignored.
- `setDefault()` — uses `lockForUpdate()` for concurrency safety.
- `delete()` — deleting the default promotes the oldest remaining address.

---

## 2. Business Rules & Security

1. **Role-Based Authorization**:
   - Only **Admin** and **Manager** users can access `/admin/customers/*` (existing `role:admin,manager` middleware + `CustomerPolicy`).
   - Customers manage only their own profile via the existing `ProfileController` (Epic 1); admin customer routes return `403 Forbidden` for customers/technicians.
2. **CustomerPolicy** (`App\Policies\CustomerPolicy`):
   - `viewAny/view/update/toggleStatus`: admin/manager only (staff check). The controller returns `404` for non-customer targets (e.g. viewing a technician as a customer).
   - Registered explicitly via `Gate::policy(User::class, CustomerPolicy::class)` in `AppServiceProvider` — Laravel auto-discovery looks for `UserPolicy`, so without this mapping all checks deny. (Same applies to `MaintenanceRequestPolicy` in Epic 4.)
3. **AddressPolicy** (`App\Policies\AddressPolicy`):
   - `view`: owner or staff (read-only support view); `update/delete/setDefault`: owner only. `create/viewAny`: any authenticated user.
4. **Ownership opacity**: customer address and request lookups scope to the owner first (`abort_unless(...isOwnedBy...)` → `404`), so foreign IDs cannot be probed. `user_id` is never accepted from request input — always taken from `auth()->id()`.
5. **No hard customer delete in MVP**: admin can edit (name, phone, status) and suspend/activate; email is read-only. Deleting customers would orphan requests/addresses.
6. **Address deletion guard** (added with Epic 4): addresses referenced by maintenance requests cannot be deleted (friendly error + `restrictOnDelete` at the DB level).

---

## 3. Endpoints & Route Map

### Customer Routes (`auth`):
- `GET /addresses` (`addresses.index`) — List own addresses.
- `GET /addresses/create` (`addresses.create`) — New address form.
- `POST /addresses` (`addresses.store`) — Save address (`StoreAddressRequest`: title max 100, street max 255, city max 100, notes max 2000, optional `is_default` boolean).
- `GET /addresses/{address}/edit` (`addresses.edit`) — Edit form (own only, else 404).
- `PUT /addresses/{address}` (`addresses.update`) — Update (`UpdateAddressRequest`, same rules).
- `PATCH /addresses/{address}/default` (`addresses.set-default`) — Set default address.
- `DELETE /addresses/{address}` (`addresses.destroy`) — Delete (blocked when used by requests).

### Admin / Manager Routes (`auth` + `role:admin,manager`, `admin.` prefix):
- `GET /admin/customers` (`admin.customers.index`) — Paginated list with search (name/email/phone) + active/inactive filter, `withCount('addresses')`.
- `GET /admin/customers/{customer}` (`admin.customers.show`) — Profile + read-only address list.
- `GET /admin/customers/{customer}/edit` (`admin.customers.edit`) — Edit form.
- `PUT /admin/customers/{customer}` (`admin.customers.update`) — Update name/phone/status (`Admin\UpdateCustomerRequest`).
- `PATCH /admin/customers/{customer}/toggle-status` (`admin.customers.toggle-status`) — Suspend/activate (mirrors `services.toggle-status`).

---

## 4. Test Coverage

Pest feature tests:
- `tests/Feature/Admin/CustomerTest.php`
  - Non-staff (customer/technician) get `403` on all admin customer routes.
  - Admin can list, view, update, and toggle customer status.
  - Admin gets `404` when accessing a non-customer profile.
  - Customers cannot access other profiles via admin routes (but can use their own `/profile`).
- `tests/Feature/AddressTest.php`
  - Customer can create (first becomes default), list, update, and delete own addresses.
  - Cross-customer access returns `404` for view/update/delete/set-default, and the record is unchanged.
  - Setting a second address as default unsets the first — exactly one default per customer.
  - Deleting the default promotes the oldest remaining address.
  - Validation errors on missing title/street/city; guests redirected to login.
