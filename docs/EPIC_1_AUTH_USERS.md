# Epic 1 — Authentication & Users

This document describes the technical architecture, models, database schema, business rules, endpoints, and testing coverage implemented for **Epic 1 (Authentication & Users)** in HomeTech.

---

## 1. Domain Entities & Database Schema

### 1.1 Users Table (`users`)
Represents all system actors across the four distinct user roles.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, Auto Increment | Unique User ID |
| `name` | `varchar(255)` | Not Null | Full user name |
| `phone` | `varchar(30)` | Nullable | Contact phone number |
| `email` | `varchar(255)` | Unique, Not Null | Account email address |
| `email_verified_at` | `timestamp` | Nullable | Verification timestamp |
| `password` | `varchar(255)` | Not Null | Hashed password (`bcrypt`) |
| `role` | `varchar(255)` | Index, Default `'customer'` | User role (`UserRole` enum) |
| `remember_token` | `varchar(100)` | Nullable | "Remember me" session token |
| `created_at`, `updated_at` | `timestamp` | Nullable | Timestamps |

---

## 2. Roles & Authorization

### 2.1 User Roles Enum (`App\Enums\UserRole`)
Defined backed enum with 4 distinct roles:
- `UserRole::Customer` (`'customer'`): Default role for public registrations. Can browse services, request maintenance, manage personal addresses, approve additional work, and pay invoices.
- `UserRole::Admin` (`'admin'`): Complete platform management (technicians, services, categories, schedules, work orders, invoices, and reports).
- `UserRole::Technician` (`'technician'`): Field operational role. Can view assigned jobs, log diagnoses, record labor and materials, request additional work, and complete visits.
- `UserRole::Manager` (`'manager'`): High-level operational oversight, reports, dashboards, discount authorizations, and administrative privileges.

### 2.2 Role Middleware (`App\Http\Middleware\RoleMiddleware`)
- Registered alias: `'role'` in `bootstrap/app.php`.
- Route usage: `middleware(['auth', 'role:admin,manager'])`.
- Enforces strict server-side authorization checks. Returns `403 Forbidden` for unauthorized roles.

---

## 3. Endpoints & Route Map

### 3.1 Authentication & Registration (Guest Only)
- `GET /register` (`register`) — Customer registration form (`RegisteredUserController@create`).
- `POST /register` (`register.store`) — Validates registration, creates Customer user, and authenticates session (`RegisteredUserController@store`).
- `GET /login` (`login`) — Login form (`AuthenticatedSessionController@create`).
- `POST /login` (`login.store`) — Validates credentials, regenerates session (`AuthenticatedSessionController@store`).

### 3.2 Authenticated Profile & Session Management
- `POST /logout` (`logout`) — Invalidates session and logs out user (`AuthenticatedSessionController@destroy`).
- `GET /profile` (`profile.edit`) — Displays profile editing interface (`ProfileController@edit`).
- `PUT /profile` (`profile.update`) — Updates name, phone, and unique email (`ProfileController@update`).
- `PUT /profile/password` (`password.update`) — Updates and hashes user password with confirmation validation (`ProfileController@updatePassword`).
- `GET /dashboard` (`dashboard`) — Main customer & authenticated user overview.

---

## 4. Form Requests & Validation

1. **`RegisterUserRequest`**:
   - `name`: `['required', 'string', 'max:255']`
   - `email`: `['required', 'string', 'email', 'max:255', 'unique:users,email']`
   - `phone`: `['nullable', 'string', 'max:30']`
   - `password`: `['required', 'confirmed', Rules\Password::defaults()]`
2. **`UpdateProfileRequest`**:
   - `name`: `['required', 'string', 'max:255']`
   - `email`: `['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)]`
   - `phone`: `['nullable', 'string', 'max:30']`
3. **`UpdatePasswordRequest`**:
   - `current_password`: `['required', 'current_password']`
   - `password`: `['required', 'confirmed', Rules\Password::defaults()]`

---

## 5. Test Coverage

Comprehensive Pest feature tests located in:
- `tests/Feature/AuthenticationTest.php`:
  - Registers a customer and authenticates them.
  - Rejects invalid registration data (duplicate email, mismatched password, missing fields).
  - Logs in with valid credentials and logs out successfully.
- `tests/Feature/RoleAuthorizationTest.php`:
  - Forbids customers and technicians from accessing admin routes (`403 Forbidden`).
  - Allows administrators and managers into the admin area.
- `tests/Feature/ProfileTest.php`:
  - Authenticated user can update profile details (name, email, phone).
  - Authenticated user can change password with valid current password verification.
