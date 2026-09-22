# HomeTech — Product Requirements Document (PRD)

**Version:** 1.0  
**Status:** Draft / MVP Planning  
**Product Type:** Home Maintenance Service Management Platform

---

## 1. Product Overview

### 1.1 Product Name

**HomeTech**

### 1.2 Product Description

HomeTech is a web-based home maintenance service management platform for companies that provide services such as:

- Plumbing
- Electrical
- Air Conditioning
- Painting
- Appliance Repair

The platform allows customers to request maintenance services while enabling the company to manage technicians, appointments, work orders, materials, invoices, payments, and customer feedback.

### 1.3 Product Vision

Provide a centralized system that manages the complete maintenance lifecycle from the customer's initial request through technician assignment, service execution, invoicing, payment, and job completion.

---

# 2. Business Problem

The maintenance company currently manages requests through phone calls, messaging applications, spreadsheets, or manual processes.

This creates several problems:

- Maintenance requests can be forgotten or delayed.
- Administrators have difficulty assigning suitable technicians.
- Technicians may receive conflicting appointments.
- There is no centralized history of customer maintenance jobs.
- Material usage is difficult to track.
- Final prices may differ from initial estimates without a clear record.
- Additional work may be performed without documented customer approval.
- Invoices and payments are difficult to track.
- Management lacks reliable operational and financial reports.

HomeTech will centralize these processes in one system.

---

# 3. Product Goals

The system should allow the company to:

1. Receive and manage customer maintenance requests.
2. Assign suitable technicians to maintenance jobs.
3. Prevent technician scheduling conflicts.
4. Track maintenance jobs throughout their lifecycle.
5. Record diagnosis, labor, materials, and work notes.
6. Maintain accurate inventory records.
7. Handle additional work through customer approval.
8. Generate invoices based on actual work performed.
9. Track full and partial payments.
10. Handle cancellations according to company policy.
11. Collect customer feedback.
12. Provide useful operational, financial, technician, and inventory reports.

---

# 4. Users and Roles

## 4.1 Customer

Customers can:

- Register and log in.
- Manage their profile.
- Manage service addresses.
- Browse available services.
- Submit maintenance requests.
- Select preferred appointment dates/times.
- View request status.
- View technician/job information when available.
- Approve or reject additional work.
- View invoices.
- Make payments.
- Request cancellation.
- View maintenance history.
- Submit feedback after completed jobs.

---

## 4.2 Admin

Administrators can:

- Manage customers.
- Manage technicians.
- Manage services and service categories.
- Manage inventory items.
- Review maintenance requests.
- Approve or reject requests.
- Assign technicians.
- Schedule and reschedule appointments.
- Monitor active jobs.
- Manage cancellations.
- Manage invoices and payments.
- View reports.
- Perform authorized corrections.

---

## 4.3 Technician

Technicians can:

- View assigned jobs.
- View job details.
- View customer and service information.
- View scheduled appointments.
- Start a maintenance visit.
- Record diagnosis.
- Record labor.
- Record materials used.
- Add work notes.
- Upload before/after photos.
- Request customer approval for additional work.
- Complete assigned jobs.

Technicians cannot:

- Assign jobs to themselves unless explicitly allowed.
- Perform unapproved additional work.
- Edit completed jobs through normal operations.
- Use more inventory than is available.

---

## 4.4 Manager

Managers can:

- View business dashboards.
- Review operational reports.
- Review revenue reports.
- Monitor technician performance.
- Monitor inventory usage.
- Approve high-value adjustments or discounts.
- Review authorized corrections to completed jobs.

## 4.5 Branch Manager

Each branch has at most one active Branch Manager, and a Branch Manager
manages at most one branch at a time. Super Admins assign, change, or
remove them per branch.

Branch Managers can, strictly within their own branch:

- View the branch performance dashboard.
- Manage branch technicians and their work schedules.
- Review maintenance requests and assign technicians.
- View branch appointments.

They cannot access other branches' data or the global admin areas.

---

# 5. Core Business Concepts

The main business entities are:

- Customer
- Customer Address
- Service Category
- Service
- Technician
- Technician Skill
- Technician Schedule
- Branch
- City
- Maintenance Request
- Appointment
- Work Order
- Diagnosis
- Labor Item
- Inventory Item
- Inventory Movement
- Material Usage
- Additional Work
- Invoice
- Invoice Item
- Payment
- Cancellation
- Review
- Notification
- Audit Log

### High-Level Relationship

```text
Customer
   │
   ├── Addresses
   │
   └── Maintenance Requests
             │
             ▼
        Maintenance Job
             │
       ┌─────┼─────┐
       ▼     ▼     ▼
 Technician Visit Materials
             │
             ▼
          Invoice
             │
             ▼
          Payment
```

---

# 6. Service Management

The company provides multiple service categories.

Initial categories:

- Plumbing
- Electrical
- Air Conditioning
- Painting
- Appliance Repair

Each service should contain:

- Name
- Description
- Category
- Base price
- Estimated duration
- Active/inactive status

### Example

```text
Category: Plumbing

Services:
- Faucet Repair
- Pipe Leakage
- Water Heater Repair
- Drain Cleaning
```

---

# 7. Customer Management

Customers should have:

- Name
- Email
- Phone number
- Password
- Account status
- Created date

Customers can maintain multiple addresses.

Each address should contain:

- Address title
- Building/street information
- City
- Additional notes
- Default address flag

### Business Rules

- A customer can have multiple addresses.
- A maintenance request must reference an address belonging to the customer.
- Customers cannot use another customer's address.

---

# 8. Maintenance Request

A customer creates a maintenance request by providing:

- Service
- Address
- Problem description
- Preferred appointment date
- Preferred time
- Optional photos

Example:

> "My AC is running but is not cooling the room."

The request initially has the status:

```text
Pending Review
```

---

# 9. Maintenance Request Workflow

The main workflow is:

```text
Pending Review
      ↓
Approved
      ↓
Technician Assigned
      ↓
Scheduled
      ↓
Technician On The Way
      ↓
In Progress
      ↓
Waiting Customer Approval
      ↓
In Progress
      ↓
Completed
      ↓
Invoiced
      ↓
Paid
      ↓
Closed
```

Not every job must use every state.

A simple job may follow:

```text
Pending Review
      ↓
Assigned
      ↓
In Progress
      ↓
Completed
      ↓
Invoiced
      ↓
Paid
      ↓
Closed
```

---

# 10. Request Review

When a customer submits a request, an administrator reviews it.

The administrator can:

- Approve the request.
- Reject the request.
- Request additional information.
- Change the appointment.
- Assign a technician.

### Business Rules

- Rejected requests cannot be assigned.
- A request must reference an active service.
- The selected address must belong to the customer.
- The requested appointment must be in the future.
- The assigned technician must support the requested service category.

---

# 11. Technician Management

Each technician should have:

- Name
- Contact information
- Employment status
- Supported service categories
- Skills
- Availability
- Current workload

A technician may support multiple service categories.

Example:

```text
Technician: Ahmed

Skills:
- Plumbing
- Water Heater Repair
```

---

# 12. Technician Assignment

An administrator assigns a technician to a maintenance job.

The system should consider:

- Service category.
- Technician skills.
- Technician availability.
- Existing appointments.
- Technician active/inactive status.

### Business Rules

A technician:

- Cannot be assigned to an unsupported service.
- Cannot have overlapping appointments.
- Cannot receive new jobs while inactive.
- Cannot be assigned to a cancelled request.

### Appointment Conflict Example

```text
Technician A

09:00 ───── 11:00
       Job #101

10:00 ───── 12:00
       Job #102 ❌
```

The system must reject Job #102 because the appointments overlap.

---

# 13. Scheduling

Appointments should contain:

- Maintenance request
- Technician
- Date
- Start time
- End time
- Status

Possible appointment statuses:

```text
Scheduled
Confirmed
Rescheduled
Cancelled
Completed
```

### Business Rules

- Appointment must have a valid technician.
- Appointment must be in the future when created.
- A technician cannot have overlapping appointments.
- Cancelled appointments cannot be started.
- Rescheduling must preserve conflict validation.

---

# 14. Technician Visit / Work Order

When the technician arrives, the work order becomes active.

The technician records:

### Diagnosis

Example:

> "The AC compressor capacitor is damaged."

### Labor

Example:

```text
AC Diagnosis       100 EGP
Capacitor Repair   150 EGP
Labor              200 EGP
```

### Materials

Example:

```text
1 × Capacitor
2 × Copper Connectors
```

### Notes

Technicians can record:

- Work performed
- Problems found
- Recommendations
- Customer notes

### Photos

Technicians can optionally upload:

- Before-service photos.
- After-service photos.

---

# 15. Inventory Management

The company maintains inventory for materials used during maintenance jobs.

Example:

```text
Material: Capacitor
Current Stock: 25
```

If a technician uses one:

```text
25 → 24
```

The system records an inventory movement containing:

- Inventory item
- Quantity
- Movement type
- Work order
- Technician
- Date
- Notes

### Movement Types

Examples:

```text
Purchase
Adjustment
Consumption
Return
Reversal
```

### Business Rules

- Stock cannot become negative.
- Material usage must reference a work order.
- Material consumption must create an inventory movement.
- Inventory changes must be traceable.
- Reversing material usage requires an authorized operation.

---

# 16. Additional Work

During a visit, the technician may discover additional problems.

### Example

Original request:

> "Fix leaking faucet."

Technician discovers:

> "The main water pipe is also damaged."

The technician creates an additional work request:

```text
Description:
Replace damaged main pipe.

Additional Cost:
500 EGP
```

### Additional Work Statuses

```text
Pending Approval
Approved
Rejected
Completed
```

### Business Rules

- Additional work cannot be charged without customer approval.
- Rejected additional work cannot be included in the invoice.
- Approved additional work can be performed and billed.
- Customer approval must be recorded with a timestamp.
- The system should record who requested and who approved/rejected the additional work.

---

# 17. Pricing

The system should distinguish between estimated and actual costs.

### Example

Initial estimate:

```text
Estimated Price: 300 EGP
```

After inspection:

```text
Labor:       250 EGP
Materials:   180 EGP
Additional:  100 EGP
---------------------
Final Total: 530 EGP
```

The final amount can differ from the initial estimate.

The system should track:

- Estimated price.
- Labor cost.
- Material cost.
- Additional work cost.
- Discount.
- Final amount.

---

# 18. Discounts

The company may allow discounts.

Discounts may be:

- Fixed amount.
- Percentage-based.

### Business Rules

- Discount cannot make the invoice total negative.
- High-value discounts may require manager approval.
- Applied discounts must be recorded.
- Discount changes should be auditable.

---

# 19. Invoice Management

After the work is completed, the system generates an invoice.

### Example

```text
Invoice #INV-10052

Service
AC Maintenance              300 EGP

Labor                        200 EGP

Materials
Capacitor                    150 EGP
Connectors                    50 EGP

Additional Work              100 EGP

Subtotal                     800 EGP
Discount                      50 EGP
--------------------------------
Total                        750 EGP
```

### Invoice Statuses

```text
Draft
Issued
Partially Paid
Paid
Cancelled
```

### Business Rules

- Invoice items must reference actual work performed or approved charges.
- Rejected additional work cannot appear on the invoice.
- A cancelled invoice cannot receive payments.
- Paid invoices cannot be modified through normal operations.

---

# 20. Payments

The system supports:

- Full payments.
- Partial payments.
- Multiple payments.

### Example

```text
Invoice:        1,000 EGP

Payment #1:       400 EGP
Payment #2:       300 EGP
Payment #3:       300 EGP

Remaining:          0 EGP
```

### Business Rules

- Payment cannot exceed the remaining invoice balance.
- Paid invoices cannot accept additional payments.
- Every payment must reference an invoice.
- Payment records cannot be silently deleted.
- Payment status must affect invoice status.

---

# 21. Cancellation

Customers may request cancellation of a maintenance job.

The system checks the cancellation policy.

### Example Policy

```text
More than 24 hours before appointment
→ No cancellation fee

Less than 24 hours before appointment
→ 10% cancellation fee

After technician starts the job
→ Cancellation not allowed
```

The exact policy should be configurable.

### Business Rules

- Cancellation must record the reason.
- Cancellation fees must be recorded separately.
- Cancelled jobs cannot be started.
- The system must prevent invalid cancellation state transitions.

---

# 22. Job Completion

A technician can complete a work order only when required information has been recorded.

Before completion:

- Diagnosis must be recorded.
- Labor must be recorded if applicable.
- Materials must be recorded if used.
- Additional work must be resolved.
- Technician notes should be completed.

After completion:

```text
Work Order
    ↓
Completed
    ↓
Invoice Generated
    ↓
Payment
    ↓
Closed
```

### Important Rule

A completed work order cannot be edited through normal technician operations.

Authorized administrators/managers may make corrections, and such corrections must be logged.

---

# 23. Customer Reviews

After a job is completed, the customer can submit a review.

Review contains:

- Rating
- Comment
- Submission date

### Business Rules

- Only the customer who owns the job can review it.
- A review can only be created for a completed job.
- A customer can submit only one review per job.

---

# 24. Notifications

The system should notify users about important events.

## Customer Notifications

- Request submitted.
- Request approved.
- Technician assigned.
- Appointment changed.
- Technician on the way.
- Additional work requires approval.
- Additional work approved/rejected.
- Invoice generated.
- Payment received.
- Job completed.

## Technician Notifications

- New job assigned.
- Job rescheduled.
- Job cancelled.
- Additional work approved/rejected.

For MVP, notifications can be stored as database notifications.

Email/SMS/WhatsApp notifications can be introduced later.

---

# 25. Admin Dashboard

The dashboard should provide an operational overview.

Example:

```text
Today's Jobs             18
Pending Requests          7
Active Jobs               5
Completed Today          11
Unpaid Invoices           9
Low Stock Items           4
```

The dashboard should also show:

- Upcoming appointments.
- Recently created requests.
- Unassigned jobs.
- Jobs waiting for customer approval.
- Overdue invoices.
- Low-stock inventory items.

---

# 26. Reports

## 26.1 Revenue Reports

- Daily revenue.
- Monthly revenue.
- Revenue by service.
- Revenue by technician.
- Outstanding invoices.

## 26.2 Job Reports

- Completed jobs.
- Cancelled jobs.
- Pending jobs.
- Jobs by service.
- Average completion time.

## 26.3 Technician Reports

- Jobs assigned.
- Jobs completed.
- Jobs cancelled.
- Revenue generated.
- Customer ratings.

## 26.4 Inventory Reports

- Current stock.
- Material consumption.
- Low-stock items.
- Most-used materials.
- Inventory movements.

---

# 27. Audit Logging

Important business operations should be recorded.

Example:

```text
Admin: Ahmed
Action: Updated Invoice #1005
Old Total: 800 EGP
New Total: 750 EGP
Reason: Customer discount
Date: 20 Sep 2026
```

Operations that may require auditing:

- Invoice modifications.
- Payment changes.
- Inventory adjustments.
- Completed job corrections.
- Discounts.
- Cancellation fee changes.
- Customer approval decisions.

---

# 28. Core Business Rules

The following rules are critical to the system.

### BR-001 — Technician Skill Matching

A technician can only be assigned to a job for a service category they support.

### BR-002 — Appointment Conflict

A technician cannot have overlapping appointments.

### BR-003 — Inventory Availability

Material consumption cannot cause inventory stock to become negative.

### BR-004 — Inventory Traceability

Every material consumption must create an inventory movement linked to the relevant work order.

### BR-005 — Additional Work Approval

Additional work requires customer approval before it can be performed and billed.

### BR-006 — Payment Limit

A payment cannot exceed the remaining invoice balance.

### BR-007 — Completed Job Protection

Completed jobs cannot be modified through normal technician operations.

### BR-008 — Cancellation Policy

Cancellation fees depend on the timing and state of the job.

### BR-009 — Review Ownership

Only the customer who owns the completed job can submit its review.

### BR-010 — Invoice Integrity

Invoice items must correspond to valid service, labor, material, or approved additional-work charges.

### BR-011 — Authorization

Sensitive operations require the appropriate role or permission.

### BR-012 — Auditability

Important financial, inventory, and workflow changes must be traceable.

---

# 29. Security Requirements

The system should provide:

- Authentication.
- Role-based authorization.
- Password hashing.
- CSRF protection.
- Input validation.
- Authorization policies.
- Protection against unauthorized resource access.
- Secure file upload handling.
- Server-side validation for all important business rules.

Authorization must be enforced on the server even if the frontend hides restricted actions.

---

# 30. Performance Requirements

The system should:

- Use pagination for large datasets.
- Avoid unnecessary database queries.
- Use appropriate eager loading.
- Add indexes to frequently queried columns.
- Avoid loading unnecessary columns.
- Use database transactions for multi-step operations.
- Use queued jobs for appropriate background tasks.

---

# 31. Reliability and Transactions

Financial and inventory operations should be transactional.

Example:

```text
Complete Job
    ↓
Record Materials
    ↓
Decrease Inventory
    ↓
Create Invoice
```

If one critical operation fails, the system should prevent a partially completed business operation.

Example:

```text
Inventory update succeeds
Invoice creation fails
        ↓
Rollback
```

The system should return the data to its previous consistent state.

---

# 32. MVP Scope

## Epic 1 — Authentication & Users

- Registration.
- Login.
- Logout.
- Roles.
- Profile management.

## Epic 2 — Services

- Service categories.
- Services.
- Service pricing.
- Service duration.
- Activate/deactivate services.

## Epic 3 — Customers

- Customer profiles.
- Customer addresses.
- Address management.

## Epic 4 — Maintenance Requests

- Create request.
- Review request.
- Approve/reject request.
- Request status.
- Request history.

## Epic 5 — Technicians

- Technician profiles.
- Technician skills.
- Technician availability.
- Technician assignment.

## Epic 6 — Scheduling

- Appointments.
- Conflict detection.
- Rescheduling.
- Cancellation.

## Epic 7 — Work Orders

- Diagnosis.
- Labor.
- Materials.
- Work notes.
- Photos.
- Job completion.

## Epic 8 — Inventory

- Inventory items.
- Stock levels.
- Stock movements.
- Material consumption.
- Low-stock monitoring.

## Epic 9 — Additional Work

- Create additional work.
- Customer approval.
- Customer rejection.
- Approved work execution.
- Additional charges.

## Epic 10 — Billing

- Invoice generation.
- Invoice items.
- Discounts.
- Payments.
- Partial payments.
- Invoice status.

## Epic 11 — Reviews

- Customer ratings.
- Customer feedback.
- Review history.

## Epic 12 — Dashboard & Reports

- Operations dashboard.
- Revenue reports.
- Job reports.
- Technician reports.
- Inventory reports.

---

# 33. Future Features

The following features are outside the MVP:

- Online payment gateway.
- SMS notifications.
- WhatsApp integration.
- Mobile application.
- GPS technician tracking.
- Automatic technician assignment.
- Subscription maintenance plans.
- Customer loyalty program.
- Multiple branches.
- Multi-company support.
- Advanced analytics.
- AI-assisted technician assignment.

---

# 34. Suggested Technology Stack

The project is intended as a Laravel MVC portfolio project.

### Backend

- PHP
- Laravel
- Laravel MVC
- Eloquent ORM

### Database
- MySQL is the required database engine for development, testing, and production.
- Configure the connection with `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.
- SQLite is not supported for application or test environments unless explicitly approved.

### Authentication & Authorization

- Laravel authentication.
- Policies/Gates.
- Role/permission system.

### Validation

- Laravel Form Requests.
- Server-side business-rule validation.

### Background Processing

- Laravel Queues where appropriate.
- Laravel Scheduler where appropriate.

### Notifications

- Laravel Notifications.
- Database notifications for MVP.

### Testing

- PHPUnit or Pest.
- Feature tests.
- Unit tests for complex business rules.

### Frontend

The MVP may use Laravel Blade with:

- Blade templates.
- HTML/CSS.
- JavaScript.
- Bootstrap or another UI framework if desired.

---

# 35. Suggested Application Structure

A practical Laravel MVC structure:

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
│
├── Models/
│
├── Services/
│
├── Policies/
│
├── Notifications/
│
├── Events/
│
├── Listeners/
│
├── Enums/
│
└── Jobs/
```

Business logic should not be placed entirely inside controllers.

Controllers should coordinate the request while more complex business operations can be handled by dedicated services.

---

# 36. Development Principles

The project should focus on realistic business implementation rather than unnecessary architectural complexity.

Priorities:

1. Correct business rules.
2. Clear domain workflows.
3. Secure authorization.
4. Database consistency.
5. Transactions for critical operations.
6. Testable business logic.
7. Clean and maintainable code.
8. Good user experience.

The project should avoid adding patterns only for the purpose of making the architecture look more advanced.

---

# 37. MVP Success Criteria

The MVP is considered successful when:

- A customer can submit a maintenance request.
- An admin can review and approve it.
- An appropriate technician can be assigned.
- The system prevents scheduling conflicts.
- The technician can perform and document the job.
- Materials used are deducted from inventory.
- Additional work requires customer approval.
- A final invoice can be generated.
- Customers can make full or partial payments.
- Jobs can be completed and closed.
- Customers can review completed jobs.
- Admins can monitor operations and basic reports.
- Important financial and inventory changes are auditable.

---

# 38. End-to-End Example

### Scenario: AC Repair

A customer submits:

```text
Service:
AC Repair

Problem:
AC is running but not cooling.

Address:
Customer's Home

Preferred Date:
25 September

Preferred Time:
10:00 AM
```

### Step 1 — Request

```text
Status: Pending Review
```

### Step 2 — Admin Review

Admin approves the request.

```text
Status: Approved
```

### Step 3 — Assignment

Admin selects an AC technician.

The system checks:

- Technician supports AC repair.
- Technician is active.
- Technician has no appointment conflict.

```text
Status: Scheduled
```

### Step 4 — Technician Visit

Technician diagnoses:

> Faulty capacitor.

Labor:

```text
200 EGP
```

Material:

```text
Capacitor — 150 EGP
```

### Step 5 — Additional Work

Technician discovers another issue:

> Damaged electrical connector.

Additional work:

```text
100 EGP
```

Customer receives an approval request.

Customer approves.

### Step 6 — Completion

Final charges:

```text
Service:           300 EGP
Labor:             200 EGP
Capacitor:         150 EGP
Connector:         100 EGP
--------------------------------
Total:             750 EGP
```

### Step 7 — Invoice

```text
Invoice: INV-10052
Status: Issued
Total: 750 EGP
```

### Step 8 — Payment

Customer pays:

```text
750 EGP
```

Invoice becomes:

```text
Paid
```

### Step 9 — Completion

Work order becomes:

```text
Closed
```

### Step 10 — Review

Customer submits:

```text
Rating: 5/5
Comment: Technician was professional and arrived on time.
```

This represents the complete business lifecycle that HomeTech is designed to manage.
