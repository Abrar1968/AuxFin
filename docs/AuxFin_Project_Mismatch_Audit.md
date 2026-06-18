# AuxFin Project Mismatch Audit

Audit date: 2026-04-29  
Scope: `docs/`, backend Laravel app, database migrations, routes, services, events, frontend Vue app, stores, services, composables, and tests.

This report lists concrete mismatches between the binding AuxFin agent instructions / SRS docs and the current implementation. It is an audit artifact only; no application code was changed.

## Executive Summary

The codebase implements most major modules, but several non-negotiable architecture rules are not currently met. The highest-risk gaps are framework version drift, missing Form Request validation, synchronous payroll processing, controller business logic, frontend realtime subscriptions outside `useRealTime`, and design-token drift.

## Critical Mismatches

### 1. Backend Framework Version Does Not Match Required Stack

- Expected: Laravel 11 only, PHP 8.3+.
- Found: `composer.json` requires PHP `^8.2` and `laravel/framework` `^12.0`.
- Evidence:
  - `composer.json:12` declares `"php": "^8.2"`.
  - `composer.json:13` declares `"laravel/framework": "^12.0"`.
- Impact: Violates the absolute backend technology constraint and can cause API, middleware, scheduler, and package behavior differences from the SRS baseline.
- Recommended fix: Pin PHP to `^8.3` and Laravel to `^11.0`, then run the full QA matrix.

### 2. Form Request Layer Is Missing

- Expected: All validation must live in dedicated `app/Http/Requests/*` Form Request classes.
- Found: `app/Http/Requests` does not exist, and controllers validate inline with `$request->validate(...)`.
- Evidence:
  - Missing directory: `app/Http/Requests`.
  - Examples: `app/Http/Controllers/Admin/EmployeeController.php:37`, `app/Http/Controllers/Admin/PayrollController.php:48`, `app/Http/Controllers/Employee/LoanController.php:74`, `app/Http/Controllers/Employee/MessageController.php:48`.
- Impact: Violates the service/controller/request architecture and makes validation harder to reuse, test, and authorize.
- Recommended fix: Add Form Request classes per POST/PUT/action endpoint and replace inline validation signatures with typed request classes.

### 3. Payroll Processing Is Synchronous

- Expected: Payroll processing must run as an async queue job and never block the HTTP request.
- Found: `PayrollController` calls `PayrollService::processMonth()` directly in both single and bulk processing.
- Evidence:
  - `app/Http/Controllers/Admin/PayrollController.php:58`
  - `app/Http/Controllers/Admin/PayrollController.php:93`
- Impact: Large payroll runs can block HTTP requests, time out, and violate the payroll engine rule.
- Recommended fix: Add queued jobs for single/bulk payroll processing; controller should dispatch the job and return `202 Accepted` or a tracked job response.

### 4. Controllers Contain Business Logic and Direct Writes

- Expected: Controllers are thin orchestrators; services contain all business logic and writes.
- Found: Multiple controllers perform transactions, Eloquent creates/updates/deletes, calculations, and workflow transitions directly.
- Evidence:
  - Employee create/update/archive logic in `app/Http/Controllers/Admin/EmployeeController.php:61`, `app/Http/Controllers/Admin/EmployeeController.php:143`, `app/Http/Controllers/Admin/EmployeeController.php:163`.
  - Payroll override recalculation in `app/Http/Controllers/Admin/PayrollController.php:132` through `app/Http/Controllers/Admin/PayrollController.php:156`.
  - Payroll status transitions in `app/Http/Controllers/Admin/PayrollController.php:61`, `app/Http/Controllers/Admin/PayrollController.php:96`, `app/Http/Controllers/Admin/PayrollController.php:167`.
- Impact: Violates the service pattern and duplicates business rules outside canonical service classes.
- Recommended fix: Move each module's create/update/delete/workflow logic into services such as `EmployeeService`, `PayrollService`, and existing domain services.

## High Mismatches

### 5. Event Interfaces Use `ShouldBroadcastNow`, Not Required `ShouldBroadcast`

- Expected: Every significant Pusher event class implements `ShouldBroadcast`.
- Found: Base event classes implement `ShouldBroadcastNow`, inherited by concrete events.
- Evidence:
  - `app/Events/AdminBroadcastEvent.php:7`
  - `app/Events/EmployeeBroadcastEvent.php:7`
  - `app/Events/InsightStreamed.php:8`
- Impact: Events broadcast immediately instead of through the queue path implied by `ShouldBroadcast`.
- Recommended fix: Switch base broadcast events to `ShouldBroadcast` unless explicitly approved otherwise.

### 6. Frontend Realtime Logic Exists In Views

- Expected: All Pusher / Echo subscription logic must live exclusively in `resources/js/composables/useRealTime.js`.
- Found: Views subscribe to `window.Echo` directly.
- Evidence:
  - `resources/js/views/employee/loans/LoanStatusView.vue:126`
  - `resources/js/views/employee/loans/LoanStatusView.vue:136`
  - `resources/js/views/employee/messages/InboxView.vue:245`
  - `resources/js/views/admin/loans/LoanManagementView.vue:555`
  - `resources/js/views/admin/loans/LoanManagementView.vue:565`
  - `resources/js/views/admin/messages/MessageCenterView.vue:498`
- Impact: Breaks the frontend layering rule and increases leak/unsubscribe risk.
- Recommended fix: Move all view-level Echo code into `useRealTime.js`; expose feature-specific subscribe/unsubscribe functions.

### 7. Views Import Stores Directly

- Expected: Views should use composables; composables bridge stores and services.
- Found: Views call Pinia stores directly.
- Evidence:
  - `resources/js/views/admin/analytics/AnalyticsOverviewView.vue:133` uses `useAnalyticsStore()`.
  - `resources/js/views/auth/LoginView.vue:74` uses `useAuthStore()`.
  - `resources/js/views/admin/messages/MessageCenterView.vue:269` uses `useAuthStore()`.
  - `resources/js/views/employee/messages/InboxView.vue:132` uses `useAuthStore()`.
- Impact: Breaks strict layer separation and spreads state orchestration across views.
- Recommended fix: Route view needs through composables such as `useAuth`, `useAnalytics`, and feature-specific realtime/message composables.

### 8. Token / Local Storage Writes Outside `auth.store.js`

- Expected: `localStorage` writes must be centralized in `auth.store.js`.
- Found: Sidebar stores UI section order in `localStorage`.
- Evidence:
  - `resources/js/components/layout/Sidebar.vue:240`
  - `resources/js/components/layout/Sidebar.vue:254`
- Impact: Violates the strict localStorage rule. If UI preferences need persistence, this needs explicit architecture approval or a dedicated persistence utility rule.
- Recommended fix: Either remove persistence or add an approved UI preferences store/utility and update the architecture rule.

### 9. Design Tokens Drift From Required Values

- Expected: Design tokens from the instructions must be used exactly for brand colors and radii.
- Found: Global CSS overrides core colors and uses larger card radii.
- Evidence:
  - `resources/css/app.css:24` sets `--color-primary: #0f766e` instead of required `#4F46E5`.
  - `resources/css/app.css:25` sets `--color-secondary: #0284c7` instead of required `#7C3AED`.
  - `resources/css/app.css:87` uses `border-radius: 18px`.
  - `resources/css/app.css:103` uses `border-radius: 20px`.
- Impact: Visual system is inconsistent with the documented AuxFin design tokens.
- Recommended fix: Restore required token values and route card radii through `--radius-*`.

### 10. Decorative Gradient Orbs / Radial Backgrounds Conflict With UI Instructions

- Expected: Do not add discrete orbs, gradient orbs, or bokeh-style blob backgrounds.
- Found: Global CSS uses radial gradient background layers and auth orb animation classes.
- Evidence:
  - `resources/css/app.css:64` through `resources/css/app.css:66`
  - `resources/css/app.css:137` through `resources/css/app.css:141`
- Impact: Conflicts with current frontend design instructions and can create a one-note visual theme.
- Recommended fix: Replace with restrained full-width surface/band styling using approved tokens.

## Medium Mismatches

### 11. Database Timestamp Rule Is Not Applied Uniformly

- Expected: Every table must have `created_at` and `updated_at` timestamps.
- Found: Several ledger/support tables only define `created_at` or no standard timestamps.
- Evidence:
  - `loan_repayments` only has `created_at`: `database/migrations/2026_04_12_080100_create_finerp_core_tables.php:101`.
  - `company_snapshots` only has `created_at`: `database/migrations/2026_04_12_080100_create_finerp_core_tables.php:221`.
  - `message_reads` has `read_at` but no `created_at` / `updated_at`: `database/migrations/2026_04_12_080100_create_finerp_core_tables.php:244`.
  - `public_holidays` only has `created_at`: `database/migrations/2026_04_12_080100_create_finerp_core_tables.php:257`.
  - `audit_logs` only has `created_at`: `database/migrations/2026_04_12_080100_create_finerp_core_tables.php:270`.
- Impact: Violates the global schema convention. Note: some SRS examples also omit `updated_at` for ledger-style tables, so this needs a final policy decision.
- Recommended fix: Either standardize all tables on `$table->timestamps()` or explicitly amend the schema rule for immutable ledger tables.

### 12. Payroll API Response Shape Does Not Follow Standard Wrapper

- Expected: Success responses should return wrapper shapes such as `{ data, message }`.
- Found: Some endpoints return raw collections or custom unwrapped payloads.
- Evidence:
  - `app/Http/Controllers/Admin/PayrollController.php:29` returns `response()->json($rows)`.
  - `app/Http/Controllers/Admin/PayrollController.php:39` returns the payslip payload directly.
- Impact: Inconsistent API contracts increase frontend branching and drift from response-format rules.
- Recommended fix: Wrap non-locked endpoint responses consistently unless a QA-locked contract requires the current shape.

### 13. Admin Payroll List Is Not Paginated

- Expected: All list endpoints support `page` and `per_page`, default `20`.
- Found: Admin payroll month endpoint returns all records with `get()`.
- Evidence: `app/Http/Controllers/Admin/PayrollController.php:22` through `app/Http/Controllers/Admin/PayrollController.php:27`.
- Impact: Large employee counts can create slow responses and inconsistent list behavior.
- Recommended fix: Use `paginate($request->integer('per_page', 20))`; update frontend and QA if contract changes are approved.

### 14. Ownership Middleware Is Mostly Passive For Route-Scoped Resources

- Expected: OwnershipCheck verifies every employee-facing resource endpoint.
- Found: Employee routes use the middleware group, but the middleware only checks route parameters named `employee_id`, model-bound objects, or input `employee_id`. Current employee routes use IDs such as `/employee/loans/{id}` and `/employee/messages/{id}` without route model binding names checked by the middleware.
- Evidence:
  - Routes: `routes/api.php:180`, `routes/api.php:193`.
  - Middleware checks: `app/Http/Middleware/OwnershipCheck.php:24` through `app/Http/Middleware/OwnershipCheck.php:31`.
- Impact: Controllers currently enforce ownership with explicit `where('employee_id', ...)`, so runtime behavior is partly protected, but the middleware is not the primary enforcement layer required by the SRS.
- Recommended fix: Use route model binding names checked by middleware or make the middleware resolve known employee route resources by ID.

### 15. Extra External Packages Need Approval Trace

- Expected: Do not add new Composer packages without explicit approval; frontend has no external component/chart libraries, and Pusher/Echo is the only permitted third-party service.
- Found: Existing package set includes `jspdf` and `jspdf-autotable`.
- Evidence:
  - `package.json:29`
  - `package.json:30`
- Impact: These may be intentional for PDF generation, but they are third-party additions beyond the explicitly allowed frontend stack.
- Recommended fix: Keep if already approved; otherwise replace with browser print/server PDF flow or record approval in project docs.

## Documentation-Level Mismatches

### 16. ROI Meter Doc Conflicts With Core Revenue Recognition Rule

- Expected: AuxFin recognizes revenue only when `invoices.payment_completed_at IS NOT NULL`.
- Found: `docs/Accrual Accounting ROI Meter Explained.md` says earned revenue can be recorded as Accounts Receivable even if clients have not paid yet.
- Evidence: `docs/Accrual Accounting ROI Meter Explained.md`, section "Step B: Monthly Earned Revenue".
- Impact: This is valid accrual-accounting theory, but conflicts with AuxFin's locked cash-confirmed recognized revenue rule.
- Recommended fix: Update the ROI doc to distinguish booked/accrual revenue from AuxFin recognized revenue and avoid implying unpaid invoices count as recognized revenue.

### 17. Button Matrix Still Contains Historical Payroll Timeout

- Expected: Button QA currently reports 34/38 pass, 3 warn, 1 fail, with rerun verification pass.
- Found: The docs preserve both the historical `/admin/payroll` timeout and later rerun pass.
- Evidence: `docs/QA_Button_Matrix_Admin_Employee.md`, Admin row 3 and "Rerun Verification".
- Impact: This is not a code mismatch, but it is an unresolved documentation ambiguity for QA status.
- Recommended fix: Add a dated resolution note explaining whether `/admin/payroll` is now accepted as pass or still requires investigation.

## Positive Findings

- Core required backend folders mostly exist: Admin/Employee/Analytics controllers, services, algorithms, models, events, console commands, and middleware.
- Most monetary fields in migrations use `decimal(...)`, not floats/integers.
- Bank account masking is implemented on `Employee` with hidden raw `bank_account_number`.
- Sanctum expiry is configured to default to 480 minutes.
- Scheduler entries exist for snapshots, recurring expenses, depreciation, and overdue invoices.
- Owner equity routes and tables exist, including business owners and owner-linked entries.
- Frontend services centralize Axios through `resources/js/services/api.service.js`; no views import Axios directly in the reviewed search.

## Recommended Remediation Order

listed in order of criticality and likely remediation effort:
1. Backend framework version drift { no need to change anything. keep the current status}
2. Missing Form Request validation.{ yes do it }
3. Synchronous payroll processing{ yes, do everything. this is a critical mismatch with major performance implications. }
4. Controller business logic and direct writes {yes. do everything to move business logic into services. this is a critical mismatch that violates the service pattern and can cause rule drift. }
5. Event interfaces use `ShouldBroadcastNow` { no change needed unless there is a specific reason to switch to `ShouldBroadcast` and queue events. }
6. Frontend realtime subscriptions outside `useRealTime` { yes, move all Echo subscription logic into `useRealTime.js` to enforce the layering rule and reduce leak risk. }
7. Views import stores directly { yes, refactor views to use composables that bridge to stores instead of direct store imports. }
8. Token / localStorage writes outside `auth.store.js` { yes, either remove localStorage usage from the sidebar or route it through an approved persistence utility or store. }
9. Design token drift { yes, restore the required design token values for colors and radii to match the documented design system. }
10. Decorative gradient orbs / radial backgrounds { yes, replace the current radial gradient backgrounds and auth orb animations with restrained surface styling that follows the approved design tokens and does not add unapproved decorative elements. }
11. Database timestamp rule not applied uniformly{ yes, either standardize all tables on `created_at` and `updated_at` or explicitly amend the schema rule for immutable ledger tables. }
12. Payroll API response shape does not follow standard wrapper { yes, wrap payroll endpoint responses in the standard `{ data, message }` format unless a QA-locked contract requires the current shape. }
13. Admin payroll list is not paginated { yes, implement pagination on the admin payroll list endpoint and update the frontend accordingly if the contract changes. }
14. Ownership middleware is mostly passive for route-scoped resources { yes, refactor ownership middleware to actively check employee-facing resources by route model binding or ID resolution to ensure it is the primary enforcement layer for employee resource access. }
15. Extra external packages need approval trace  {no need any changes}
16. ROI meter doc conflicts with core revenue recognition rule { yes, update the ROI meter documentation to clarify the distinction between booked/accrual revenue and AuxFin's cash-confirmed recognized revenue to resolve the conflict and avoid confusion. }
17. Button matrix still contains historical payroll timeout { yes, add a dated resolution note in the button matrix documentation explaining the current status of the `/admin/payroll` timeout issue and whether it is now considered a pass or still requires investigation. }

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan tinker --execute="require base_path('scripts/qa/production_matrix_runner.php');"
npm run test
npm run build
```
