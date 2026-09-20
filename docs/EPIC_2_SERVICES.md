# Epic 2 — Services & Service Categories

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 2 (Services)** in HomeTech.

---

## 1. Domain Entities & Database Schema

### 1.1 Service Categories (`service_categories`)
Represents grouping categories for maintenance services and technician specialties (e.g., Plumbing, Electrical, Air Conditioning, Painting, Appliance Repair).

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Category ID |
| `name` | `varchar(255)` | Unique, Not Null | Display name (e.g. "Air Conditioning") |
| `slug` | `varchar(255)` | Unique, Not Null | URL slug (e.g. "air-conditioning") |
| `description` | `text` | Nullable | Detailed category description |
| `icon` | `varchar(100)` | Nullable | Icon identifier for UI |
| `is_active` | `tinyint(1)` | Default `1` | Active flag |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

### 1.2 Services (`services`)
Represents specific maintenance jobs offered to customers.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique Service ID |
| `service_category_id` | `bigint unsigned` | FK -> `service_categories.id` (`restrictOnDelete`) | Parent Category |
| `name` | `varchar(255)` | Not Null | Service name (e.g. "AC Cleaning") |
| `slug` | `varchar(255)` | Unique, Not Null | URL slug (e.g. "ac-cleaning") |
| `description` | `text` | Nullable | Service scope, diagnosis, and details |
| `base_price` | `decimal(10,2)` | Default `0.00` | Starting / base price in EGP |
| `estimated_duration_minutes` | `unsigned int` | Default `60` | Estimated job duration in minutes |
| `is_active` | `tinyint(1)` | Default `1` | Active availability status |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

**Indexes**:
- `services_slug_unique` (`slug`)
- `services_service_category_id_is_active_index` (`service_category_id`, `is_active`)

---

## 2. Business Rules & Security

1. **Role-Based Authorization**:
   - Only **Admin** and **Manager** users can access Admin CRUD routes (`/admin/categories/*`, `/admin/services/*`) and toggle activation status.
   - Customers, Technicians, and Unauthenticated users are strictly forbidden (`403 Forbidden` / `302 Redirect to login`).
2. **Category Deletion Safety**:
   - A category cannot be deleted if it has associated services. An explicit validation error and warning is returned.
3. **Public Catalog Visibility**:
   - Public browse listing (`/services`) and detail pages (`/services/{slug}`) only show active services that belong to active categories (`scopeActive()`).
   - Inactive services return `404 Not Found` to public users.
4. **Automatic Slug Generation**:
   - Models automatically generate slugs using `Str::slug()` if not explicitly provided during creation or editing.

---

## 3. Endpoints & Route Map

### Public / Customer Routes:
- `GET /services` (`services.index`) — Browse active services with category filter pills and keyword search.
- `GET /services/{slug}` (`services.show`) — View detailed service page with base price, estimated duration, and booking CTA.

### Admin / Manager Routes:
- `GET /admin/categories` (`admin.categories.index`) — List categories with service counts.
- `GET /admin/categories/create` (`admin.categories.create`) — Form to create a category.
- `POST /admin/categories` (`admin.categories.store`) — Save new category.
- `GET /admin/categories/{category}/edit` (`admin.categories.edit`) — Form to edit category.
- `PUT /admin/categories/{category}` (`admin.categories.update`) — Update category.
- `DELETE /admin/categories/{category}` (`admin.categories.destroy`) — Safe delete category.
- `GET /admin/services` (`admin.services.index`) — List services with category/status/keyword filters.
- `GET /admin/services/create` (`admin.services.create`) — Form to create a service.
- `POST /admin/services` (`admin.services.store`) — Save new service.
- `GET /admin/services/{service}/edit` (`admin.services.edit`) — Form to edit service.
- `PUT /admin/services/{service}` (`admin.services.update`) — Update service.
- `PATCH /admin/services/{service}/toggle-status` (`admin.services.toggle-status`) — Toggle active/inactive.
- `DELETE /admin/services/{service}` (`admin.services.destroy`) — Delete service.

---

## 4. Test Coverage

Comprehensive Pest feature tests located in:
- `tests/Feature/Admin/ServiceCategoryTest.php`
- `tests/Feature/Admin/ServiceTest.php`
- `tests/Feature/ServiceBrowseTest.php`
