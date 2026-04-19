# AuxFin — Backend Development Guide

**Laravel 11 (PHP 8.3+) · MySQL 8.0 · Pusher · Laravel Sanctum**

> Version 2.0 | Extracted from SRS v2.0 | Ready for Development

---

## Table of Contents

1. [Stack & Constraints](#1-stack--constraints)
2. [Project Architecture](#2-project-architecture)
3. [Database Schema — All Tables](#3-database-schema--all-tables)
4. [Authentication & RBAC](#4-authentication--rbac)
5. [Payroll Engine](#5-payroll-engine)
6. [Loan Management System](#6-loan-management-system)
7. [Revenue & Project Ledger](#7-revenue--project-ledger)
8. [Analytics Algorithms](#8-analytics-algorithms)
9. [CMGR & Growth Engine](#9-cmgr--growth-engine)
10. [Real-Time Events (Pusher)](#10-real-time-events-pusher)
11. [Message & Query System](#11-message--query-system)
12. [Late Attendance & Work Schedule](#12-late-attendance--work-schedule)
13. [Full API Reference](#13-full-api-reference)
14. [Security Requirements](#14-security-requirements)
15. [Non-Functional Requirements](#15-non-functional-requirements)
16. [Development Roadmap](#16-development-roadmap)

---

## 1. Stack & Constraints

> **Strict — No Deviations Permitted**

| Technology | Version / Notes |
|---|---|
| Backend Framework | Laravel 11 (PHP 8.3+) — REST API only |
| Database | MySQL 8.0 — Eloquent ORM exclusively |
| Real-time | Pusher + Laravel Echo — the only permitted third-party |
| Authentication | Laravel Sanctum — SPA token-based |
| Charts Data | Served raw from API — no chart libraries on backend |
| Queue Driver | Database/Redis — for async payroll & analytics jobs |
| Scheduler | Laravel Artisan Scheduler — monthly snapshot jobs |

---

## 2. Project Architecture

### 2.1 Folder Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                    ← All admin controllers
│   │   │   ├── EmployeeController.php
│   │   │   ├── PayrollController.php
│   │   │   ├── LoanController.php
│   │   │   ├── ProjectController.php
│   │   │   ├── InvoiceController.php
│   │   │   ├── ExpenseController.php
│   │   │   ├── LiabilityController.php
│   │   │   ├── AssetController.php
│   │   │   ├── MessageController.php
│   │   │   └── SettingsController.php
│   │   ├── Employee/                 ← Employee portal controllers
│   │   │   ├── DashboardController.php
│   │   │   ├── SalaryController.php
│   │   │   ├── LoanController.php
│   │   │   ├── LeaveController.php
│   │   │   ├── AttendanceController.php
│   │   │   └── MessageController.php
│   │   └── Analytics/                ← Analytics engine controllers
│   │       ├── OverviewController.php
│   │       ├── GrowthController.php
│   │       ├── ForecastController.php
│   │       └── AnomalyController.php
│   ├── Middleware/
│   │   ├── AdminOnly.php
│   │   ├── EmployeeOnly.php
│   │   ├── OwnershipCheck.php
│   │   └── ForceHttps.php
│   └── Requests/                     ← Form Request validation classes
├── Services/
│   ├── PayrollService.php            ← Core payroll calculation engine
│   ├── LoanService.php               ← Loan lifecycle management
│   ├── ForecastService.php           ← Cash flow forecasting
│   ├── SnapshotService.php           ← Monthly company snapshot capture
│   └── NotificationService.php       ← Pusher event dispatcher
├── Algorithms/
│   ├── CMGR.php                      ← Compound Monthly Growth Rate
│   ├── MonteCarloForecast.php        ← 3-scenario probabilistic forecast
│   ├── ZScoreAnomaly.php             ← Expense anomaly detection
│   ├── LinearRegression.php          ← Revenue projection
│   ├── MovingAverage.php             ← EMA/SMA trend smoothing
│   ├── ARHealthScore.php             ← Accounts receivable health score
│   └── PayrollEfficiencyIndex.php    ← Revenue vs payroll ratio
├── Models/
│   ├── User.php
│   ├── Employee.php
│   ├── Department.php
│   ├── SalaryMonth.php
│   ├── Loan.php
│   ├── LoanRepayment.php
│   ├── Leave.php
│   ├── Attendance.php
│   ├── Client.php
│   ├── Project.php
│   ├── Invoice.php
│   ├── Expense.php
│   ├── Liability.php
│   ├── Asset.php
│   ├── CompanySnapshot.php
│   ├── EmployeeMessage.php
│   ├── MessageRead.php
│   ├── PublicHoliday.php
│   └── AuditLog.php
├── Events/
│   ├── SalaryProcessed.php
│   ├── SalaryPaid.php
│   ├── LoanApplied.php
│   ├── LoanApproved.php
│   ├── LoanRejected.php
│   ├── LeaveApplied.php
│   ├── LeaveDecision.php
│   ├── InvoiceOverdue.php
│   ├── LiabilityDueSoon.php
│   ├── MessageNew.php
│   ├── MessageReplied.php
│   ├── MessageResolved.php
│   └── MessageActionTaken.php
├── Listeners/                        ← Pusher broadcast handlers
└── Console/
    └── Commands/
        ├── CaptureMonthlySnapshot.php
        ├── ProcessRecurringExpenses.php
        ├── DepreciateAssets.php
        └── FlagOverdueInvoices.php
```

### 2.2 Route Groups

```php
// routes/api.php
Route::prefix('api')->group(function () {

    // Public
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/change-passkey', [AuthController::class, 'changePasskey']);

        // Admin routes
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::apiResource('employees', Admin\EmployeeController::class);
            Route::post('employees/{id}/reset-passkey', ...);
            // ... all admin routes
        });

        // Employee routes
        Route::middleware('employee')->prefix('employee')->group(function () {
            Route::get('dashboard', [Employee\DashboardController::class, 'index']);
            // ... all employee routes
        });
    });
});
```

---

## 3. Database Schema — All Tables

### 3.1 users

Central authentication table. Passkey stored as bcrypt hash. Plain passkey shown once to admin then nulled.

```sql
CREATE TABLE users (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(150) NOT NULL,
  email        VARCHAR(200) NOT NULL UNIQUE,
  passkey      VARCHAR(64)  NOT NULL,         -- bcrypt hashed
  passkey_plain VARCHAR(20) NULL,             -- shown once, then NULL'd
  role         ENUM('super_admin','admin','employee') NOT NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at TIMESTAMP NULL,
  last_login_ip VARCHAR(45) NULL,
  created_by   BIGINT UNSIGNED NULL,
  created_at   TIMESTAMP,
  updated_at   TIMESTAMP,
  deleted_at   TIMESTAMP NULL                -- soft delete
);
```

### 3.2 employees

Extended profile linked to users. Contains full salary structure and deduction configuration.

```sql
CREATE TABLE employees (
  id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id               BIGINT UNSIGNED NOT NULL UNIQUE,
  employee_code         VARCHAR(20) NOT NULL UNIQUE,  -- EMP-0042
  department_id         BIGINT UNSIGNED NULL,
  designation           VARCHAR(150) NOT NULL,
  date_of_joining       DATE NOT NULL,
  bank_account_number   VARCHAR(30) NULL,             -- masked in API
  bank_name             VARCHAR(100) NULL,

  -- Salary Structure
  basic_salary          DECIMAL(12,2) NOT NULL DEFAULT 0,
  house_rent            DECIMAL(12,2) NOT NULL DEFAULT 0,
  conveyance            DECIMAL(12,2) NOT NULL DEFAULT 0,
  medical_allowance     DECIMAL(12,2) NOT NULL DEFAULT 0,
  pf_rate               DECIMAL(5,2)  NOT NULL DEFAULT 0,   -- % of basic
  tds_rate              DECIMAL(5,2)  NOT NULL DEFAULT 0,   -- % of gross
  professional_tax      DECIMAL(10,2) NOT NULL DEFAULT 0,

  -- Late Penalty Config
  late_threshold_days   INT NOT NULL DEFAULT 3,
  late_penalty_type     ENUM('half_day','full_day') NOT NULL DEFAULT 'half_day',

  -- Work Schedule (Section 17)
  working_days_per_week TINYINT NOT NULL DEFAULT 5,  -- 1 to 7
  weekly_off_days       JSON NULL,                   -- ["friday","saturday"]

  created_at   TIMESTAMP,
  updated_at   TIMESTAMP,
  deleted_at   TIMESTAMP NULL
);
```

### 3.3 departments

```sql
CREATE TABLE departments (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(150) NOT NULL,
  head_id    BIGINT UNSIGNED NULL,  -- FK to employees
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### 3.4 salary_months

One record per employee per month. Stores all computed earnings and deductions. Status: draft → processed → paid.

```sql
CREATE TABLE salary_months (
  id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id            BIGINT UNSIGNED NOT NULL,
  month                  DATE NOT NULL,              -- First day of month

  -- Earnings
  basic_salary           DECIMAL(12,2) NOT NULL DEFAULT 0,
  house_rent             DECIMAL(12,2) NOT NULL DEFAULT 0,
  conveyance             DECIMAL(12,2) NOT NULL DEFAULT 0,
  medical_allowance      DECIMAL(12,2) NOT NULL DEFAULT 0,
  performance_bonus      DECIMAL(12,2) NOT NULL DEFAULT 0,
  festival_bonus         DECIMAL(12,2) NOT NULL DEFAULT 0,
  overtime_pay           DECIMAL(12,2) NOT NULL DEFAULT 0,
  other_bonus            DECIMAL(12,2) NOT NULL DEFAULT 0,
  gross_earnings         DECIMAL(12,2) NOT NULL DEFAULT 0,

  -- Deductions
  tds_deduction          DECIMAL(12,2) NOT NULL DEFAULT 0,
  pf_deduction           DECIMAL(12,2) NOT NULL DEFAULT 0,
  professional_tax       DECIMAL(10,2) NOT NULL DEFAULT 0,
  unpaid_leave_deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
  late_penalty_deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
  loan_emi_deduction     DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_deductions       DECIMAL(12,2) NOT NULL DEFAULT 0,

  -- Result
  net_payable            DECIMAL(12,2) NOT NULL DEFAULT 0,
  days_present           INT NOT NULL DEFAULT 0,
  unpaid_leave_days      INT NOT NULL DEFAULT 0,
  late_entries           INT NOT NULL DEFAULT 0,
  expected_working_days  INT NOT NULL DEFAULT 0,

  -- Status
  status                 ENUM('draft','processed','paid') NOT NULL DEFAULT 'draft',
  processed_at           TIMESTAMP NULL,
  paid_at                TIMESTAMP NULL,
  processed_by           BIGINT UNSIGNED NULL,

  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  UNIQUE KEY uniq_emp_month (employee_id, month)
);
```

### 3.5 loans

```sql
CREATE TABLE loans (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id       BIGINT UNSIGNED NOT NULL,
  loan_reference    VARCHAR(30) NOT NULL UNIQUE,   -- LON-2025-0001
  amount_requested  DECIMAL(12,2) NOT NULL,
  amount_approved   DECIMAL(12,2) NULL,
  repayment_months  TINYINT NULL,
  emi_amount        DECIMAL(12,2) NULL,             -- approved / months
  start_month       DATE NULL,
  reason            TEXT NOT NULL,
  status            ENUM('pending','approved','rejected','active','completed') DEFAULT 'pending',
  amount_remaining  DECIMAL(12,2) NULL,
  admin_note        TEXT NULL,
  reviewed_by       BIGINT UNSIGNED NULL,
  reviewed_at       TIMESTAMP NULL,
  created_at        TIMESTAMP,
  updated_at        TIMESTAMP
);
```

### 3.6 loan_repayments

```sql
CREATE TABLE loan_repayments (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_id     BIGINT UNSIGNED NOT NULL,
  month       DATE NOT NULL,
  amount_paid DECIMAL(12,2) NOT NULL,
  created_at  TIMESTAMP
);
```

### 3.7 leaves

```sql
CREATE TABLE leaves (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id   BIGINT UNSIGNED NOT NULL,
  leave_type    ENUM('casual','sick','earned','unpaid') NOT NULL,
  from_date     DATE NOT NULL,
  to_date       DATE NOT NULL,
  days          INT NOT NULL,
  reason        TEXT NOT NULL,
  status        ENUM('pending','approved','rejected') DEFAULT 'pending',
  admin_note    TEXT NULL,
  reviewed_by   BIGINT UNSIGNED NULL,
  reviewed_at   TIMESTAMP NULL,
  created_at    TIMESTAMP,
  updated_at    TIMESTAMP
);
```

### 3.8 attendances

```sql
CREATE TABLE attendances (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id   BIGINT UNSIGNED NOT NULL,
  date          DATE NOT NULL,
  check_in      TIME NULL,
  check_out     TIME NULL,
  status        ENUM('present','absent','late','weekly_off','holiday') NOT NULL,
  is_late       TINYINT(1) NOT NULL DEFAULT 0,
  late_minutes  INT NULL,
  created_at    TIMESTAMP,
  UNIQUE KEY uniq_emp_date (employee_id, date)
);
```

### 3.9 clients

```sql
CREATE TABLE clients (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(200) NOT NULL,
  email        VARCHAR(200) NULL,
  phone        VARCHAR(30) NULL,
  address      TEXT NULL,
  contact_person VARCHAR(150) NULL,
  created_at   TIMESTAMP,
  updated_at   TIMESTAMP,
  deleted_at   TIMESTAMP NULL
);
```

### 3.10 projects

```sql
CREATE TABLE projects (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id     BIGINT UNSIGNED NOT NULL,
  name          VARCHAR(200) NOT NULL,
  description   TEXT NULL,
  contract_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  status        ENUM('active','completed','on_hold','cancelled') DEFAULT 'active',
  start_date    DATE NULL,
  end_date      DATE NULL,
  created_at    TIMESTAMP,
  updated_at    TIMESTAMP
);
```

### 3.11 invoices

```sql
CREATE TABLE invoices (
  id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id            BIGINT UNSIGNED NOT NULL,
  invoice_number        VARCHAR(30) NOT NULL UNIQUE,   -- INV-2025-0001
  amount                DECIMAL(14,2) NOT NULL,
  due_date              DATE NOT NULL,
  status                ENUM('draft','sent','partial','paid','overdue') DEFAULT 'draft',
  partial_amount        DECIMAL(14,2) NULL,
  payment_completed_at  TIMESTAMP NULL,                -- revenue recognized on this date
  notes                 TEXT NULL,
  created_at            TIMESTAMP,
  updated_at            TIMESTAMP
);
```

### 3.12 expenses

```sql
CREATE TABLE expenses (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category      VARCHAR(100) NOT NULL,
  description   TEXT NOT NULL,
  amount        DECIMAL(12,2) NOT NULL,
  expense_date  DATE NOT NULL,
  is_recurring  TINYINT(1) NOT NULL DEFAULT 0,
  recurrence    ENUM('monthly','quarterly','yearly') NULL,
  next_due_date DATE NULL,
  created_by    BIGINT UNSIGNED NOT NULL,
  created_at    TIMESTAMP,
  updated_at    TIMESTAMP
);
```

### 3.13 liabilities

```sql
CREATE TABLE liabilities (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(200) NOT NULL,
  principal_amount DECIMAL(14,2) NOT NULL,
  outstanding      DECIMAL(14,2) NOT NULL,
  interest_rate    DECIMAL(5,2) NOT NULL DEFAULT 0,
  monthly_payment  DECIMAL(12,2) NOT NULL,
  start_date       DATE NOT NULL,
  end_date         DATE NULL,
  next_due_date    DATE NULL,
  status           ENUM('active','completed','defaulted') DEFAULT 'active',
  created_at       TIMESTAMP,
  updated_at       TIMESTAMP
);
```

### 3.14 assets

```sql
CREATE TABLE assets (
  id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name                 VARCHAR(200) NOT NULL,
  category             VARCHAR(100) NOT NULL,
  purchase_date        DATE NOT NULL,
  purchase_cost        DECIMAL(14,2) NOT NULL,
  current_book_value   DECIMAL(14,2) NOT NULL,
  useful_life_months   INT NOT NULL,
  monthly_depreciation DECIMAL(12,2) NOT NULL,  -- purchase_cost / useful_life_months
  status               ENUM('active','disposed','fully_depreciated') DEFAULT 'active',
  created_at           TIMESTAMP,
  updated_at           TIMESTAMP
);
```

### 3.15 company_snapshots

Monthly financial snapshot captured by scheduler on 1st of each month. Feeds all CMGR and trend algorithms.

```sql
CREATE TABLE company_snapshots (
  id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  snapshot_month       DATE NOT NULL UNIQUE,  -- e.g. 2025-06-01
  total_revenue        DECIMAL(16,2) NOT NULL DEFAULT 0,
  total_payroll        DECIMAL(16,2) NOT NULL DEFAULT 0,
  total_opex           DECIMAL(16,2) NOT NULL DEFAULT 0,
  gross_profit         DECIMAL(16,2) NOT NULL DEFAULT 0,
  net_profit           DECIMAL(16,2) NOT NULL DEFAULT 0,
  burn_rate            DECIMAL(16,2) NOT NULL DEFAULT 0,
  cash_runway_months   DECIMAL(8,2)  NOT NULL DEFAULT 0,
  headcount            INT NOT NULL DEFAULT 0,
  total_ar             DECIMAL(16,2) NOT NULL DEFAULT 0,
  created_at           TIMESTAMP
);
```

### 3.16 employee_messages

```sql
CREATE TABLE employee_messages (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id     BIGINT UNSIGNED NOT NULL,
  thread_id       BIGINT UNSIGNED NULL,
  type            ENUM('late_appeal','deduction_dispute','leave_clarification',
                       'salary_query','loan_query','general_hr') NOT NULL,
  subject         VARCHAR(300) NOT NULL,
  body            TEXT NOT NULL,
  reference_date  DATE NULL,
  reference_month DATE NULL,
  attachments     JSON NULL,
  status          ENUM('open','under_review','resolved','rejected') DEFAULT 'open',
  priority        ENUM('normal','high') DEFAULT 'normal',
  admin_reply     TEXT NULL,
  replied_by      BIGINT UNSIGNED NULL,
  replied_at      TIMESTAMP NULL,
  action_taken    ENUM('none','deduction_reversed','mark_excused',
                       'salary_adjusted','noted') DEFAULT 'none',
  resolved_at     TIMESTAMP NULL,
  created_at      TIMESTAMP,
  updated_at      TIMESTAMP
);
```

### 3.17 message_reads

```sql
CREATE TABLE message_reads (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message_id BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  read_at    TIMESTAMP NOT NULL,
  UNIQUE KEY uniq_msg_user (message_id, user_id)
);
```

### 3.18 public_holidays

```sql
CREATE TABLE public_holidays (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(200) NOT NULL,
  date        DATE NOT NULL UNIQUE,
  is_optional TINYINT(1) DEFAULT 0,
  created_at  TIMESTAMP
);
```

### 3.19 audit_logs

```sql
CREATE TABLE audit_logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NULL,
  action      VARCHAR(100) NOT NULL,   -- e.g. 'payroll.processed', 'late.excused'
  model_type  VARCHAR(100) NULL,
  model_id    BIGINT UNSIGNED NULL,
  old_values  JSON NULL,
  new_values  JSON NULL,
  ip_address  VARCHAR(45) NULL,
  user_agent  TEXT NULL,
  created_at  TIMESTAMP
);
```

---

## 4. Authentication & RBAC

### 4.1 Admin: Create Employee Account

1. Admin fills employee creation form (name, email, designation, salary structure)
2. System auto-generates 8-character alphanumeric passkey (e.g. `Kx7#mP2q`)
3. Passkey shown ONCE to admin in a modal — never retrievable again
4. Passkey stored as bcrypt hash in `users.passkey`
5. `users.passkey_plain` set to NULL after display
6. Admin delivers passkey to employee via secure physical/messaging channel

### 4.2 Employee Login Flow

```
POST /api/auth/login
Body: { email, passkey }

1. Find user by email
2. bcrypt_verify(passkey, users.passkey)
3. Reject if is_active = 0
4. Issue Laravel Sanctum token with ability: ['employee']
5. Log: last_login_at, last_login_ip
6. Return: { token, user: { role, name, employee_code } }
```

### 4.3 Role Permissions Matrix

| Feature | Super Admin | Admin | Employee |
|---|---|---|---|
| Create/Edit Employees | Yes | Yes | No |
| Generate/Reset Passkeys | Yes | Yes | No |
| Process Payroll | Yes | Yes | No |
| View Own Payslip | Yes | Yes | Yes (own only) |
| Approve Loans | Yes | Yes | No |
| Apply for Loan | No | No | Yes (own) |
| View All Projects | Yes | Yes | No |
| View Full Analytics | Yes | Yes | No |
| View Growth Analytics | Yes | Yes | No |
| System Configuration | Yes | No | No |
| Audit Logs | Yes | Read-only | No |
| Manage Liabilities | Yes | Yes | No |
| Manage Assets | Yes | Yes | No |

### 4.4 Middleware Stack

```php
// AdminOnly.php — allows super_admin and admin
// EmployeeOnly.php — allows employee role
// OwnershipCheck.php — verifies resource belongs to authenticated employee
// ForceHttps.php — redirects to HTTPS in production
```

---

## 5. Payroll Engine

### 5.1 Complete Payroll Calculation Formula

```
GROSS_EARNINGS = basic_salary + house_rent + conveyance
               + medical_allowance + performance_bonus
               + overtime_pay + festival_bonus + other_bonus

DAILY_RATE = GROSS_EARNINGS / EXPECTED_WORKING_DAYS
(NOT divided by calendar days — reflects actual contracted days)

UNPAID_LEAVE_DEDUCTION = DAILY_RATE × unpaid_leave_days

LATE_DEDUCTION_AMOUNT = DAILY_RATE × FLOOR(confirmed_late_days / 2)

TDS_DEDUCTION       = GROSS_EARNINGS × (tds_rate / 100)
PF_DEDUCTION        = basic_salary × (pf_rate / 100)
PROFESSIONAL_TAX    = fixed monthly amount
LOAN_EMI            = active_loan.emi_amount (if any)

TOTAL_DEDUCTIONS    = TDS + PF + PROFESSIONAL_TAX
                    + LEAVE_DEDUCTION + LATE_DEDUCTION + LOAN_EMI

NET_PAYABLE         = GROSS_EARNINGS − TOTAL_DEDUCTIONS
```

### 5.2 PayrollService.php — processMonth()

```php
// app/Services/PayrollService.php

public function processMonth(Employee $emp, string $month): SalaryMonth
{
    $expectedDays = $this->getExpectedWorkingDays($emp, $month);
    $gross        = $emp->basic_salary + $emp->house_rent
                  + $emp->conveyance + $emp->medical_allowance
                  + $this->getBonuses($emp->id, $month);
    $dailyRate    = $gross / $expectedDays;

    // Late deduction: every 2 confirmed late days = 1 day deducted
    $lateDaysCount  = $this->getConfirmedLateDays($emp->id, $month);
    $lateUnits      = floor($lateDaysCount / $latePolicy->days_per_unit);
    $latePenalty    = $dailyRate * $lateUnits;

    // Unpaid leave deduction
    $unpaidLeaves = $this->getUnpaidLeaveDays($emp->id, $month);
    $leaveDeduct  = $dailyRate * $unpaidLeaves;

    $tds  = $gross * ($emp->tds_rate / 100);
    $pf   = $emp->basic_salary * ($emp->pf_rate / 100);
    $pt   = $emp->professional_tax;
    $emi  = $this->getActiveLoanEmi($emp->id);

    $totalDeductions = $tds + $pf + $pt + $leaveDeduct + $latePenalty + $emi;
    $netPayable      = $gross - $totalDeductions;

    return SalaryMonth::updateOrCreate(
        ['employee_id' => $emp->id, 'month' => $month],
        compact('gross', 'tds', 'pf', 'pt', 'leaveDeduct',
                'latePenalty', 'emi', 'totalDeductions', 'netPayable',
                'expectedDays', 'lateDaysCount', 'unpaidLeaves')
    );
}
```

### 5.3 Expected Working Days Calculation

```php
public function getExpectedWorkingDays(Employee $emp, string $month): int
{
    $daysInMonth   = Carbon::parse($month)->daysInMonth;
    $offDays       = json_decode($emp->weekly_off_days ?? '[]', true);
    $offDayCount   = $this->countWeeklyOffOccurrences($month, $offDays);
    $holidayCount  = PublicHoliday::whereYear('date', Carbon::parse($month)->year)
                       ->whereMonth('date', Carbon::parse($month)->month)
                       ->where('is_optional', 0)
                       ->count();
    return $daysInMonth - $offDayCount - $holidayCount;
}
```

### 5.4 Payroll Status Workflow

```
draft → processed → paid

draft:     Month record created, calculations editable (System / Admin)
processed: Calculations finalized, locked for override (Admin)
paid:      Salary transferred to employee (Admin)
```

### 5.5 Payslip Contents

- Employee details: Name, Code, Department, Designation
- Month & payment date
- Earnings breakdown: Basic, HRA, Conveyance, Medical, all bonuses
- Deductions breakdown: TDS, PF, Professional Tax, Leave, Late, Loan EMI
- Net payable (highlighted)
- Month-over-month delta (% change from previous month)
- Loan repayment schedule if active (remaining balance, EMI, months left)

---

## 6. Loan Management System

### 6.1 Full Loan Lifecycle

```
Employee applies → POST /api/employee/loans/apply
  ↓ Validation: no active loan, amount within policy limits
  ↓ Pusher event: Admin receives real-time loan notification
Admin approves: sets amount, repayment_months, start_month
  ↓ System computes EMI = approved_amount / repayment_months
  ↓ Monthly payroll: PayrollService deducts EMI automatically
  ↓ LoanRepayment record created each month
  ↓ loan.amount_remaining decremented; status = completed when 0
```

### 6.2 LoanService.php

```php
public function approve(Loan $loan, array $data, Admin $admin): void
{
    $loan->update([
        'amount_approved'  => $data['amount_approved'],
        'repayment_months' => $data['repayment_months'],
        'emi_amount'       => $data['amount_approved'] / $data['repayment_months'],
        'start_month'      => $data['start_month'],
        'amount_remaining' => $data['amount_approved'],
        'status'           => 'approved',
        'reviewed_by'      => $admin->id,
        'reviewed_at'      => now(),
    ]);
    event(new LoanApproved($loan));
}
```

### 6.3 Admin Loan Policy Settings

| Policy Setting | Default | Description |
|---|---|---|
| Maximum loan amount | 3× monthly salary | Configurable per employee or global |
| Maximum repayment months | 12 months | Configurable in system settings |
| Cooling period | 3 months | Wait after loan completion before applying again |
| Concurrent loans | 1 maximum | Only 1 active loan per employee at a time |

---

## 7. Revenue & Project Ledger

### 7.1 Revenue Recognition Engine

```
BOOKED_REVENUE      = SUM of all invoice amounts on project
RECOGNIZED_REVENUE  = SUM of invoices WHERE payment_completed_at IS NOT NULL
ACCOUNTS_RECEIVABLE = BOOKED_REVENUE − RECOGNIZED_REVENUE
```

**Critical Rule:** Revenue is recognized ONLY when `payment_completed_at` is set by admin (confirming actual cash receipt in bank). This prevents confusion between work-done and money-in-bank.

### 7.2 Invoice Status Workflow

```
draft → sent → partial → paid (revenue recognized)
                       ↗
               overdue (auto-flagged when due_date < today AND status != paid)
```

### 7.3 Scheduled Commands

```php
// CaptureMonthlySnapshot.php — runs on 1st of every month at 00:05
// ProcessRecurringExpenses.php — runs daily, checks next_due_date
// DepreciateAssets.php — runs on 1st of every month
// FlagOverdueInvoices.php — runs daily, flags invoices past due_date
```

---

## 8. Analytics Algorithms

### Algorithm 1: Moving Average (Trend Smoothing)

```php
// app/Algorithms/MovingAverage.php

// Simple Moving Average
public static function sma(array $values, int $n): array
{
    // Returns avg of last N periods for each position
}

// Exponential Moving Average
public static function ema(array $values, float $alpha = 0.3): array
{
    // EMA_t = alpha * value_t + (1 - alpha) * EMA_(t-1)
}
```

Purpose: Remove noise from monthly revenue/expense data to reveal true trend direction.

### Algorithm 2: Z-Score Anomaly Detection

```php
// app/Algorithms/ZScoreAnomaly.php

public static function detect(array $values, float $threshold = 2.5): array
{
    $mean   = array_sum($values) / count($values);
    $stdDev = sqrt(array_sum(array_map(
        fn($v) => pow($v - $mean, 2), $values
    )) / count($values));

    return array_map(function ($v) use ($mean, $stdDev, $threshold) {
        $z = ($v - $mean) / $stdDev;
        return ['value' => $v, 'z_score' => $z, 'is_anomaly' => abs($z) > $threshold];
    }, $values);
}
```

### Algorithm 3: Linear Regression (Revenue Forecasting)

```php
// app/Algorithms/LinearRegression.php

public static function forecast(array $y, int $periods = 3): array
{
    $n = count($y);
    $x = range(1, $n);
    // slope = (n*SUM(xy) - SUM(x)*SUM(y)) / (n*SUM(x^2) - SUM(x)^2)
    // intercept = (SUM(y) - slope*SUM(x)) / n
    // R-squared calculated for forecast confidence
}
```

### Algorithm 4: Monte Carlo Cash Flow Simulation

```php
// app/Algorithms/MonteCarloForecast.php

// Collection probabilities by AR age
private const COLLECTION_PROBS = [
    '0_30d'  => 0.95,
    '31_60d' => 0.80,
    '61_90d' => 0.60,
    '90plus' => 0.30,
];

public static function simulate(array $arItems, int $iterations = 1000): array
{
    // Runs 1,000 simulations per forecast
    // Each AR item assigned collection probability by age
    // Returns: P10 (pessimistic), P50 (base case), P90 (optimistic)
}
```

### Algorithm 5: Payroll Cost Efficiency Index

```php
// app/Algorithms/PayrollEfficiencyIndex.php

// REVENUE_PER_EMPLOYEE = total_revenue / headcount
// PAYROLL_RATIO = (total_payroll / total_revenue) * 100
// Target: < 40% | Warning: > 55% | Critical: > 70%
```

### Algorithm 6: AR Health Score

```php
// app/Algorithms/ARHealthScore.php

private const WEIGHTS = ['0_30d' => 0.95, '31_60d' => 0.80, '61_90d' => 0.60, '90plus' => 0.30];

// AR_HEALTH_SCORE = SUM(amount_i * weight_i) / total_AR * 100
// Score: 90-100 Excellent | 70-89 Good | 50-69 Watch | <50 Critical
```

---

## 9. CMGR & Growth Engine

### 9.1 CMGR Formula

```php
// app/Algorithms/CMGR.php

public static function calculate(float $initial, float $final, int $months): float
{
    // CMGR = [ (Final_Value / Initial_Value)^(1/N) - 1 ] * 100
    return (pow($final / $initial, 1 / $months) - 1) * 100;
}
```

### 9.2 CMGR Metrics Tracked

| Metric | Description | Health Signal |
|---|---|---|
| Revenue CMGR | Monthly growth of recognized revenue | Primary growth indicator |
| Headcount CMGR | Monthly growth of employee count | Hiring velocity |
| Payroll CMGR | Monthly growth of payroll expenses | Should be < Revenue CMGR |
| Net Profit CMGR | Monthly growth of net profit | Ultimate health metric |
| OpEx CMGR | Monthly growth of operating costs | Efficiency indicator |
| AR CMGR | Monthly growth of accounts receivable | Collection effectiveness |

### 9.3 Health Interpretation Rules

| Condition | Status | Action Required |
|---|---|---|
| Revenue CMGR > Payroll CMGR | Healthy — scalable growth | Continue strategy |
| Revenue CMGR < Payroll CMGR | Warning — costs outpacing revenue | Review hiring & pay increases |
| Revenue Quality Score > 80% | Excellent — strong collection | Maintain AR processes |
| Revenue Quality Score < 50% | Critical — collection risk | Immediate AR follow-up |
| Cash Runway > 12 months | Safe — comfortable position | Strategic investment |
| Cash Runway < 6 months | Critical — urgent action needed | Revenue push / cost reduction |
| AR Health Score < 50 | High bad-debt risk | Escalate overdue invoices |

---

## 10. Real-Time Events (Pusher)

### 10.1 All Pusher Events

| Event Channel | Trigger | Recipient |
|---|---|---|
| `salary.processed` | Admin processes salary | Employee personal channel |
| `salary.paid` | Admin marks salary paid | Employee personal channel |
| `loan.applied` | Employee submits loan | Admin broadcast channel |
| `loan.approved` | Admin approves loan | Employee personal channel |
| `loan.rejected` | Admin rejects loan | Employee personal channel |
| `leave.applied` | Employee applies for leave | Admin broadcast channel |
| `leave.decision` | Admin approves/rejects leave | Employee personal channel |
| `invoice.overdue` | Invoice past due date | Admin broadcast channel |
| `liability.due_soon` | Liability due in 7 days | Admin broadcast channel |
| `message.new` | Employee submits message | Admin broadcast channel |
| `message.replied` | Admin submits reply | Employee private channel |
| `message.resolved` | Admin marks resolved | Employee private channel |
| `message.action_taken` | Admin excuses late / reverses deduction | Employee private channel |

### 10.2 Example Event Class

```php
// app/Events/SalaryProcessed.php

class SalaryProcessed implements ShouldBroadcast
{
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("employee.{$this->salaryMonth->employee_id}");
    }

    public function broadcastAs(): string
    {
        return 'salary.processed';
    }
}
```

---

## 11. Message & Query System

### 11.1 Message Types

| Type Code | Label | Description |
|---|---|---|
| `late_appeal` | Late Attendance Appeal | Employee explains reason for late arrival and requests the late mark be excused |
| `deduction_dispute` | Deduction Dispute | Employee challenges a deduction amount on their payslip |
| `leave_clarification` | Leave Policy Query | Questions about remaining leave balance, leave type eligibility |
| `salary_query` | Salary Query | Discrepancy or confusion about any salary line item |
| `loan_query` | Loan Query | Questions about loan repayment schedule, balance, or eligibility |
| `general_hr` | General HR Query | Any other HR-related question not covered above |

### 11.2 Late Appeal Action Effects

| action_taken Value | System Effect |
|---|---|
| `mark_excused` | Sets `attendances.is_late = 0` for that date; late_entries count decremented; payroll recalculated if month not yet paid |
| `deduction_reversed` | Finds the `salary_months` record; zeroes `late_penalty_deduction`; recomputes `net_payable`; creates audit log entry |
| `noted` | No system change; admin reply is informational only |
| `none` | Default — admin replied but took no payroll action |

### 11.3 Message Status Lifecycle

```
open → under_review → resolved
                    → rejected
```

---

## 12. Late Attendance & Work Schedule

### 12.1 Per-Employee Work Schedule

```sql
ALTER TABLE employees ADD COLUMN working_days_per_week TINYINT NOT NULL DEFAULT 5;
-- Valid range: 1 to 7 — set by admin per employee

ALTER TABLE employees ADD COLUMN weekly_off_days JSON NULL;
-- e.g. ["friday", "saturday"] for 5-day employees
-- e.g. ["friday"] for 6-day employees
```

### 12.2 Late Deduction Policy Formula

```
LATE_DAYS_THIS_MONTH       = COUNT(attendances WHERE is_late = 1 AND not excused)
FULL_LATE_DEDUCTION_UNITS  = FLOOR(LATE_DAYS_THIS_MONTH / 2)
LATE_DEDUCTION_AMOUNT      = DAILY_RATE × FULL_LATE_DEDUCTION_UNITS

Example:
  Employee was late 5 days in June
  FLOOR(5 / 2) = 2 full-day deduction units
  Daily rate = 45000 / 22 = 2045.45
  LATE_DEDUCTION = 2045.45 × 2 = 4090.90 BDT
  1 remaining late day carries no deduction (needs another to trigger)
```

### 12.3 Late Policy Configuration (Admin Settings)

| Setting | Default | Description |
|---|---|---|
| Late days per deduction unit | 2 | How many late days trigger 1 day deduction |
| Deduction unit type | full_day | Whether each unit = full day or half day salary |
| Grace period (minutes) | 15 | Minutes past office start before marking late |
| Office start time | 09:00 | Configurable per company (global setting) |
| Carry-forward | No | Whether unmatched late day carries to next month |

---

## 13. Full API Reference

### 13.1 Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/login` | Login with email + passkey |
| POST | `/api/auth/logout` | Revoke Sanctum token |
| POST | `/api/auth/change-passkey` | Change own passkey (requires current) |

### 13.2 Admin — Employee Management

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/admin/employees` | List all employees with pagination |
| POST | `/api/admin/employees` | Create employee + generate passkey |
| GET | `/api/admin/employees/{id}` | Get full employee details |
| PUT | `/api/admin/employees/{id}` | Update employee profile/salary |
| DELETE | `/api/admin/employees/{id}` | Soft-delete employee |
| POST | `/api/admin/employees/{id}/reset-passkey` | Generate new passkey |

### 13.3 Admin — Payroll

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/admin/payroll/{month}` | All salary records for given month |
| POST | `/api/admin/payroll/process` | Process salary for employee + month |
| POST | `/api/admin/payroll/bulk-process` | Process entire month for all employees |
| PUT | `/api/admin/payroll/{id}` | Override individual salary line items |
| POST | `/api/admin/payroll/{id}/mark-paid` | Mark salary as paid |

### 13.4 Analytics Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/admin/analytics/overview` | All KPI metrics for dashboard |
| GET | `/api/admin/analytics/cmgr` | CMGR for all metrics |
| GET | `/api/admin/analytics/forecast` | Monte Carlo 3-month forecast |
| GET | `/api/admin/analytics/anomalies` | Z-score anomaly report |
| GET | `/api/admin/analytics/burn-rate` | Burn rate + runway calculation |
| GET | `/api/admin/analytics/ar-health` | AR health score breakdown |
| GET | `/api/admin/analytics/growth` | Full growth analytics data |

### 13.5 Employee Portal Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/employee/dashboard` | Dashboard KPI cards |
| GET | `/api/employee/salary` | Salary history (all months) |
| GET | `/api/employee/salary/{month}/payslip` | Full payslip breakdown |
| GET | `/api/employee/salary/{month}/payslip/pdf` | PDF download |
| GET | `/api/employee/loans` | Own loan history |
| POST | `/api/employee/loans/apply` | Apply for new loan |
| GET | `/api/employee/attendance` | Attendance records with summary |
| GET | `/api/employee/leaves` | Leave history |
| POST | `/api/employee/leaves/apply` | Apply for leave |

### 13.6 Message System Endpoints

| Method | Endpoint | Role | Description |
|---|---|---|---|
| POST | `/api/employee/messages` | Employee | Submit a new message/query |
| GET | `/api/employee/messages` | Employee | List own messages with status |
| GET | `/api/employee/messages/{id}` | Employee | View single message + admin reply |
| GET | `/api/admin/messages` | Admin | All messages, filterable |
| GET | `/api/admin/messages/{id}` | Admin | Full message detail + payroll context |
| POST | `/api/admin/messages/{id}/reply` | Admin | Submit reply + set action_taken |
| POST | `/api/admin/messages/{id}/resolve` | Admin | Mark resolved |
| POST | `/api/admin/messages/{id}/reject` | Admin | Reject with mandatory reason |

---

## 14. Security Requirements

| Requirement | Implementation |
|---|---|
| Authentication | Laravel Sanctum SPA tokens — 8h expiry |
| Passkey Security | bcrypt cost 12 — shown once — never retrievable |
| API Authorization | All routes behind `auth:sanctum` middleware |
| Role Enforcement | Custom middleware per route group (admin/employee) |
| Data Ownership | `OwnershipCheck` middleware — employees see own data only |
| SQL Injection | Eloquent ORM — parameterized queries — no raw SQL |
| XSS Prevention | Vue.js auto-escaping on frontend |
| Rate Limiting | 5/min login, 60/min API — Laravel throttle |
| HTTPS | `ForceHttps` middleware in production |
| Audit Trail | Every write → `audit_logs` with IP + user agent |
| Bank Data | Account numbers masked in API responses |

---

## 15. Non-Functional Requirements

| Metric | Target |
|---|---|
| Page Load Time | < 2 seconds (with API caching) |
| API Response Time | < 300ms at P95 percentile |
| Payroll Processing | < 5 seconds (async queue) |
| Analytics Recompute | < 30 seconds (background job) |
| System Uptime | 99.5% or better |
| Concurrent Users | 100+ simultaneous users supported |
| Mobile Responsive | Full tablet + mobile support |
| Browser Support | Chrome 90+, Firefox 88+, Safari 14+ |

---

## 16. Development Roadmap

| Phase | Duration | Deliverables |
|---|---|---|
| Phase 1: Foundation | Weeks 1–3 | Laravel setup, MySQL schema, migrations, seeders, authentication (passkey system), Sanctum setup, route groups, middleware |
| Phase 2: Core HR & Payroll | Weeks 4–6 | PayrollService, SalaryMonth management, per-employee work schedule, expected working days calc, late deduction rules, PDF payslip generation, Leave management |
| Phase 3: Financial Modules | Weeks 7–9 | Client & Project management, Invoice lifecycle with revenue recognition, Liability & amortization engine, Expense tracking + recurring expenses, Asset registry + depreciation scheduler |
| Phase 4: Loan System | Weeks 10–11 | Employee loan application, Admin approval/rejection, Pusher notifications, Auto EMI deduction in payroll, Loan repayment ledger |
| Phase 5: Real-Time & Notifications | Week 12 | Full Pusher + Laravel Echo integration, All 13 real-time event types, Audit log system, Message & Query system full CRUD |
| Phase 6: Analytics & Reporting | Weeks 13–15 | All 6 algorithms, CMGR engine, company_snapshots scheduler, P&L + Tax + AR Aging reports, analytics API endpoints |
| Phase 7: Polish & Launch | Weeks 16–17 | PDF export for all reports, System settings module, Query caching + index tuning, Security audit, Full end-to-end testing |

---

## 17. April 2026 Delta — Timeframe + Multi-Owner Equity

### 17.1 New Timeframe Contract (Day/Week/Month/Year)

- `timeframe` + `anchor_date` are supported across admin analytics/reporting/accounting APIs.
- Timeframe-aware payloads are now available in:
  - Finance overview
  - Analytics overview, CMGR, growth, forecast, anomalies
  - Reports (profit-loss, tax summary, cash flow, trial balance, balance sheet, AR aging, ledgers)

### 17.2 Multi-Owner Equity Model

- Added `business_owners` table for ownership registry:
  - `name`
  - `ownership_percentage`
  - `initial_investment`
  - `is_active`
- Added `business_owner_id` foreign key on `owner_equity_entries`.
- Equity entries can now be attributed to a specific owner while preserving legacy compatibility.

### 17.3 Owner Equity API Additions

- `GET /api/admin/owner-equity/owners`
- `POST /api/admin/owner-equity/owners`
- `PUT /api/admin/owner-equity/owners/{id}`
- `DELETE /api/admin/owner-equity/owners/{id}`
- Existing `owner-equity` CRUD remains active and now supports `business_owner_id`.

### 17.4 Validation Rules Added

- Active ownership allocation cannot exceed `100%`.
- If active owners exist, owner equity entries must include `business_owner_id`.
- Owners with linked equity rows cannot be deleted (must deactivate/update instead).

### 17.5 Extended Automated Coverage

- Feature tests now cover:
  - 3-owner equity split scenario
  - Ownership overflow guard (>100%)
  - Required owner linkage for equity entries
  - Per-owner net investment computations

Validation status at update time: passing targeted owner-equity suite and passing frontend production build.

---

*— AuxFin Backend Guide v2.0 — Laravel 11 · MySQL 8.0 · Pusher · Laravel Sanctum —*
