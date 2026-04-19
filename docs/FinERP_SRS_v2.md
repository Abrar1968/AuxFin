# SOFTWARE REQUIREMENTS SPECIFICATION

# FinERP — Enterprise Financial Intelligence & ERP Platform

**Version 2.0 | Industry-Grade Edition**

| Field | Detail |
|---|---|
| Project Name | FinERP — Enterprise Financial Intelligence & ERP |
| Version | 2.0 (Industry-Grade) |
| Backend Stack | Laravel 11 (PHP 8.3+) |
| Frontend Stack | Vue.js 3 (Composition API) + TailwindCSS v4 |
| Database | MySQL 8.0 |
| Real-time | Pusher + Laravel Echo |
| State Management | Pinia |
| Status | Final Draft — Ready for Development |

---

## 1. INTRODUCTION & VISION

### 1.1 Purpose

This SRS defines the complete functional, technical, and analytical requirements for FinERP — an enterprise-grade Financial Intelligence and Human Resource Management platform. The system transforms raw transactional data into strategic intelligence, enabling leadership to make data-driven decisions about growth, hiring, cash flow, and operations.

### 1.2 Gaps Identified from Original Proposal (Now Resolved)

The following critical components were missing from the original proposal and are fully addressed in this SRS:

| Gap Identified | Resolution in This SRS |
|---|---|
| No authentication system | Full RBAC with Admin-issued passkeys (bcrypt hashed) |
| No employee self-service | Dedicated Employee Portal with full salary & deduction visibility |
| No loan management | Employee loan application → Admin approval → auto salary deduction |
| No leave & attendance module | Full leave lifecycle with payroll integration |
| No client/CRM module | Client profile, contact history, project linkage |
| No notification system | Pusher-powered real-time alerts for all key events |
| No audit trail | Every write action logged with user, timestamp, and IP |
| No CMGR or predictive analytics | Full analytics engine with 6 advanced algorithms + CMGR |
| No department/team structure | Departments with head assignments and reporting chains |
| No payslip generation | Per-month PDF payslip with full deduction breakdown |
| No tax report export | Tax liability summary with TDS/PF remittance tracking |
| No cash flow forecasting | 3-month rolling Monte Carlo simulation forecast |
| No asset management | Asset registry with automated monthly straight-line depreciation |

### 1.3 Technology Constraints (Strict — No Deviations)

| Technology | Version / Notes |
|---|---|
| Backend | Laravel 11 (PHP 8.3+) — REST API |
| Frontend | Vue.js 3 — Composition API only |
| Styling | TailwindCSS v4 — No external UI libraries |
| Database | MySQL 8.0 — Eloquent ORM |
| Real-time | Pusher + Laravel Echo — Only permitted third-party |
| State Management | Pinia — No Vuex |
| Charts | Custom Canvas/SVG components — No chart libraries |
| Authentication | Laravel Sanctum — SPA token-based |

---

## 2. STAKEHOLDERS & USER CLASSES

### 2.1 User Roles

| Role | Description | Access Level |
|---|---|---|
| Super Admin | Company owner / CEO | Full system access — all modules |
| Admin | HR Manager / Finance Manager | Configurable per module by Super Admin |
| Employee | Staff member | Personal portal only — own data |

### 2.2 Core Access Principle

No employee can self-register. Only administrators can create employee accounts. Each employee receives a unique system-generated passkey from the admin. The passkey is shown only once during creation and is stored as a bcrypt hash. Employees use their email + passkey to log in to their personal portal.

### 2.3 Role Permissions Matrix

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

---

## 3. SYSTEM ARCHITECTURE OVERVIEW

### 3.1 Backend Folder Structure (Laravel 11)

```
app/Http/Controllers/Admin/          ← All admin controllers
app/Http/Controllers/Employee/       ← Employee portal controllers
app/Http/Controllers/Analytics/      ← Analytics engine controllers
app/Services/PayrollService.php      ← Core payroll calculation
app/Services/LoanService.php         ← Loan lifecycle management
app/Services/ForecastService.php     ← Cash flow forecasting
app/Algorithms/CMGR.php              ← Compound Monthly Growth Rate
app/Algorithms/MonteCarloForecast.php ← 3-scenario cash flow
app/Algorithms/ZScoreAnomaly.php     ← Expense anomaly detection
app/Algorithms/LinearRegression.php  ← Revenue projection
app/Algorithms/MovingAverage.php     ← EMA/SMA trend smoothing
app/Events/ + app/Listeners/         ← Pusher event system
app/Console/Commands/                ← Scheduled monthly jobs
```

### 3.2 Frontend Folder Structure (Vue.js 3)

```
src/views/admin/Dashboard.vue        ← Main admin dashboard
src/views/admin/Analytics/           ← Analytics + growth pages
src/views/employee/Dashboard.vue     ← Employee portal home
src/views/employee/Salary/           ← Salary history + payslip
src/views/employee/Loans/            ← Loan application + history
src/components/charts/               ← All custom chart components
src/stores/                          ← Pinia state stores
src/composables/                     ← Reusable Vue composables
```

---

## 4. DATABASE SCHEMA

### 4.1 Table: users

Central authentication table. Passkey stored as bcrypt hash. Plain passkey shown once to admin then nulled.

| Column | Type | Description |
|---|---|---|
| id | BIGINT UNSIGNED PK | Auto-increment primary key |
| name | VARCHAR(150) | Full name |
| email | VARCHAR(200) UNIQUE | Login email |
| passkey | VARCHAR(64) | bcrypt hashed passkey |
| passkey_plain | VARCHAR(20) NULL | Shown once, then set NULL |
| role | ENUM | super_admin / admin / employee |
| is_active | TINYINT(1) | Account enabled status |
| last_login_at | TIMESTAMP NULL | Last successful login |
| created_by | BIGINT UNSIGNED | Admin who created account |

### 4.2 Table: employees

Extended profile linked to users. Contains full salary structure and deduction configuration.

| Column | Type | Description |
|---|---|---|
| employee_code | VARCHAR(20) UNIQUE | Human-readable ID e.g. EMP-0042 |
| department_id | BIGINT FK | Links to departments table |
| basic_salary | DECIMAL(12,2) | Base monthly salary |
| house_rent | DECIMAL(12,2) | HRA component |
| conveyance | DECIMAL(12,2) | Conveyance allowance |
| medical_allowance | DECIMAL(12,2) | Medical allowance |
| pf_rate | DECIMAL(5,2) | PF as % of basic salary |
| tds_rate | DECIMAL(5,2) | TDS as % of gross |
| professional_tax | DECIMAL(10,2) | Fixed monthly deduction |
| late_threshold_days | INT | Late entries before penalty applies |
| late_penalty_type | ENUM | half_day or full_day penalty |

### 4.3 Table: salary_months

One record per employee per month. Stores all computed earnings and deductions. Status: draft → processed → paid.

### 4.4 Table: loans

Full loan lifecycle. Employee applies → Admin approves with amount and repayment months → EMI computed → deducted from future salary months.

| Column | Type | Description |
|---|---|---|
| loan_reference | VARCHAR(30) UNIQUE | e.g. LON-2025-0001 |
| amount_requested | DECIMAL(12,2) | What employee requested |
| amount_approved | DECIMAL(12,2) NULL | What admin approved (can differ) |
| repayment_months | TINYINT | Number of months to repay |
| emi_amount | DECIMAL(12,2) NULL | approved_amount / repayment_months |
| start_month | DATE NULL | Month when deductions begin |
| status | ENUM | pending/approved/rejected/active/completed |
| amount_remaining | DECIMAL(12,2) | Outstanding balance |

### 4.5 Table: company_snapshots

Monthly financial snapshot captured by scheduler on 1st of each month. Feeds all CMGR and trend algorithms.

| Column | Description |
|---|---|
| snapshot_month | First day of month — unique per month |
| total_revenue | Sum of recognized invoice payments |
| total_payroll | Sum of all net_payable for the month |
| total_opex | Sum of all expenses for the month |
| gross_profit | total_revenue - total_payroll |
| net_profit | gross_profit - total_opex - liability_interest |
| burn_rate | Average monthly outflow (computed) |
| cash_runway_months | Available cash / burn_rate |
| headcount | Active employee count |

---

## 5. AUTHENTICATION & ACCESS CONTROL

### 5.1 Admin: Create Employee Account

- Admin fills employee creation form (name, email, designation, salary structure)
- System auto-generates 8-character alphanumeric passkey (e.g. Kx7#mP2q)
- Passkey shown ONCE to admin in a modal — never retrievable again
- Passkey stored as bcrypt hash in users.passkey
- users.passkey_plain set to NULL after display
- Admin delivers passkey to employee via secure physical/messaging channel

### 5.2 Employee Login Flow

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

### 5.3 Security Configuration

| Security Layer | Implementation |
|---|---|
| Token Type | Laravel Sanctum SPA tokens |
| Token Expiry | 8 hours (configurable in settings) |
| Rate Limiting | 5 login attempts/minute, 60 API calls/minute |
| Passkey Hashing | bcrypt with cost factor 12 |
| Role Enforcement | Custom middleware: AdminOnly, EmployeeOnly, OwnershipCheck |
| SQL Injection | Eloquent ORM parameterized queries only |
| Audit Logging | Every write action → audit_logs table |
| Sensitive Data | Bank account numbers masked in API responses |

---

## 6. ADVANCED PAYROLL ENGINE

### 6.1 Complete Payroll Calculation Formula

```
GROSS_EARNINGS = basic_salary + house_rent + conveyance
               + medical_allowance + performance_bonus
               + overtime_pay + festival_bonus + other_bonus

DAILY_RATE = GROSS_EARNINGS / days_in_month

UNPAID_LEAVE_DEDUCTION = DAILY_RATE x unpaid_leave_days

LATE_PENALTY: IF late_entries > threshold:
  penalty_days = FLOOR((late_entries - threshold) / threshold)
  half_day: LATE_PENALTY = DAILY_RATE x 0.5 x penalty_days
  full_day: LATE_PENALTY = DAILY_RATE x penalty_days

TDS_DEDUCTION = GROSS_EARNINGS x (tds_rate / 100)
PF_DEDUCTION = basic_salary x (pf_rate / 100)
PROFESSIONAL_TAX = fixed monthly amount
LOAN_EMI = active_loan.emi_amount (if any)

TOTAL_DEDUCTIONS = TDS + PF + PROFESSIONAL_TAX
                 + LEAVE_DEDUCTION + LATE_PENALTY + LOAN_EMI

NET_PAYABLE = GROSS_EARNINGS - TOTAL_DEDUCTIONS
```

### 6.2 Payroll Status Workflow

| Status | Description | Who Changes It |
|---|---|---|
| draft | Month record created, calculations editable | System / Admin |
| processed | Calculations finalized, locked for override | Admin |
| paid | Salary transferred to employee | Admin |

### 6.3 Payslip Contents

- Employee details: Name, Code, Department, Designation
- Month & payment date
- Earnings breakdown: Basic, HRA, Conveyance, Medical, all bonuses
- Deductions breakdown: TDS, PF, Professional Tax, Leave, Late, Loan EMI
- Net payable (highlighted)
- Month-over-month delta (% change from previous month)
- Loan repayment schedule if active (remaining balance, EMI, months left)

---

## 7. EMPLOYEE SELF-SERVICE PORTAL

### 7.1 Access Control

Employee logs in with admin-issued passkey. The portal displays ONLY their own data. No cross-employee data access is possible — enforced by OwnershipCheck middleware at the API level.

### 7.2 Employee Dashboard — Metric Cards

| Card | Data Shown |
|---|---|
| Current Month Net Salary | Amount + status badge (Draft / Processed / Paid) |
| Total Earned YTD | Sum of all net_payable this year |
| Total Deducted YTD | Sum of all deductions this year (TDS + PF + other) |
| Outstanding Loan Balance | Remaining loan principal if active loan exists |

### 7.3 Salary History

Full table of all processed months with: Month, Gross Earnings, Bonuses, Total Deductions, Loan EMI, Net Payable, Status. Clicking any row expands a full payslip breakdown. Download button generates PDF payslip for that month.

### 7.4 Loan Application

- Employee submits: amount, reason, preferred repayment months (1-12)
- System shows preview: estimated monthly deduction if approved
- Validation: no active loan allowed (1 at a time)
- Admin receives real-time Pusher notification
- Employee sees status: Pending → Approved/Rejected with admin note
- If approved: monthly EMI appears in all future salary payslips

### 7.5 Employee Charts

| Chart | Type | Purpose |
|---|---|---|
| Monthly net salary trend | Bar chart (canvas) | Last 12 months comparison |
| Earnings vs Deductions | Donut chart (SVG) | Visual split of gross vs deductions |
| Loan repayment progress | Progress bar (CSS) | Amount repaid vs total approved |
| Bonus history | Bar chart (canvas) | Performance + festival + overtime |

---

## 8. REVENUE & PROJECT LEDGER

### 8.1 Revenue Recognition Engine

```
BOOKED_REVENUE = SUM of all invoice amounts on project
RECOGNIZED_REVENUE = SUM of invoices WHERE payment_completed_at IS NOT NULL
ACCOUNTS_RECEIVABLE = BOOKED_REVENUE - RECOGNIZED_REVENUE
```

Critical Rule: Revenue is recognized ONLY when payment_completed_at is set by admin (confirming actual cash receipt in bank). This prevents confusion between work-done and money-in-bank.

### 8.2 Invoice Status Workflow

| Status | Description |
|---|---|
| draft | Invoice created but not yet sent to client |
| sent | Invoice delivered to client, awaiting payment |
| partial | Partial payment received |
| paid | Full payment received — revenue recognized |
| overdue | Auto-flagged when due_date < today and status != paid |

---

## 9. LOAN MANAGEMENT SYSTEM

### 9.1 Full Loan Lifecycle

- Employee applies via portal → POST /api/employee/loans/apply
- Validation: no active loan, amount within policy limits
- Pusher event: Admin receives real-time loan notification
- Admin approves: sets amount, repayment_months, start_month
- System computes EMI = approved_amount / repayment_months
- Monthly payroll: PayrollService deducts EMI automatically
- LoanRepayment record created each month
- loan.amount_remaining decremented; status = completed when 0

### 9.2 Admin Loan Settings

| Policy Setting | Default | Description |
|---|---|---|
| Maximum loan amount | 3x monthly salary | Configurable per employee or global |
| Maximum repayment months | 12 months | Configurable in system settings |
| Cooling period | 3 months | Wait after loan completion before applying again |
| Concurrent loans | 1 maximum | Only 1 active loan per employee at a time |

---

## 10. ADVANCED ANALYTICS ALGORITHMS

### Algorithm 1: Simple & Exponential Moving Average (Trend Smoothing)

Purpose: Remove noise from monthly revenue/expense data to reveal true trend direction.

```
SMA: avg of last N periods — used for revenue and payroll trends
EMA: EMA_t = alpha * value_t + (1 - alpha) * EMA_(t-1)
Alpha = 0.3 (recent data weighted more heavily)
```

Displayed as: Smoothed trend line overlaid on all time-series charts.

### Algorithm 2: Z-Score Anomaly Detection

Purpose: Automatically flag unusual spikes in expenses, salary overrides, or invoice amounts.

```
z_score = (value - mean) / standard_deviation
Anomaly flag: |z_score| > 2.5 threshold
```

Use cases: OpEx spikes, unusual salary changes, inconsistent invoice amounts. Flagged items appear highlighted in admin dashboard.

### Algorithm 3: Linear Regression (Revenue Forecasting)

Purpose: Project next 3-6 months of recognized revenue based on historical data.

```
slope = (n*SUM(xy) - SUM(x)*SUM(y)) / (n*SUM(x^2) - SUM(x)^2)
intercept = (SUM(y) - slope*SUM(x)) / n
R-squared calculated to show forecast confidence
```

Displayed as: Dashed projection line extending from the last data point on revenue chart.

### Algorithm 4: Monte Carlo Cash Flow Simulation

Purpose: Probabilistic 3-month cash flow forecast accounting for payment uncertainty.

```
Runs 1,000 simulations per forecast
Each AR item assigned a collection probability:
  0-30d: 95%, 31-60d: 80%, 61-90d: 60%, 90+: 30%
Each simulation randomly realizes each AR item
Outputs: P10 (pessimistic), P50 (base case), P90 (optimistic)
```

Displayed as: Three-band area chart — pessimistic (red), base (blue), optimistic (green).

### Algorithm 5: Payroll Cost Efficiency Index

Purpose: Track whether revenue growth is outpacing payroll growth.

```
REVENUE_PER_EMPLOYEE = total_revenue / headcount
PAYROLL_RATIO = (total_payroll / total_revenue) * 100
Target: < 40% | Warning: > 55% | Critical: > 70%
```

Displayed as: Gauge chart with green/amber/red zones.

### Algorithm 6: Accounts Receivable Health Score

Purpose: Score quality of outstanding receivables by collection likelihood.

```
AR_HEALTH_SCORE = SUM(amount_i * weight_i) / total_AR * 100
Weights: 0-30d=0.95, 31-60d=0.80, 61-90d=0.60, 90+d=0.30
Score: 90-100 Excellent | 70-89 Good | 50-69 Watch | <50 Critical
```

---

## 11. CMGR & COMPANY GROWTH ANALYTICS

### 11.1 Compound Monthly Growth Rate Formula

```
CMGR = [ (Final_Value / Initial_Value)^(1/N) - 1 ] * 100
N = number of months between first and last data point
```

### 11.2 CMGR Calculated For All Key Metrics

| Metric | Description | Health Signal |
|---|---|---|
| Revenue CMGR | Monthly growth of recognized revenue | Primary growth indicator |
| Headcount CMGR | Monthly growth of employee count | Hiring velocity |
| Payroll CMGR | Monthly growth of payroll expenses | Should be < Revenue CMGR |
| Net Profit CMGR | Monthly growth of net profit | Ultimate health metric |
| OpEx CMGR | Monthly growth of operating costs | Efficiency indicator |
| AR CMGR | Monthly growth of accounts receivable | Collection effectiveness |

### 11.3 Growth Analytics Dashboard Sections

| Section | Description |
|---|---|
| Growth Velocity Cards | 6 metric cards each with current value, CMGR %, trailing 6-month CMGR, trend arrow |
| Growth Rate Chart | Rolling 6-month CMGR line chart: Revenue vs Payroll vs OpEx |
| Growth Efficiency Ratio | Revenue CMGR / Headcount CMGR (>1.0 = scalable, <1.0 = inefficient) |
| Revenue Quality Score | recognized_revenue / booked_revenue * 100 |
| 12-Month Projection | Linear regression forecast for revenue, payroll, net profit |
| Cash Runway Calculator | Admin inputs available cash; system computes months of runway |
| Cohort Comparison | Side-by-side period comparison (e.g. H1 2024 vs H1 2025) |
| Burn Rate Trend | Rolling 3-month average of monthly outflows with trend line |

### 11.4 Growth Health Interpretation

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

## 12. FRONTEND ARCHITECTURE (Vue.js 3 + TailwindCSS v4)

### 12.1 Custom Chart Components (No Libraries)

| Component | Tech | Used For |
|---|---|---|
| BarChart.vue | Canvas API | Monthly revenue, payroll, bonus trends |
| LineChart.vue | Canvas API | Trends with EMA smoothing overlay |
| DonutChart.vue | Inline SVG | Earnings vs deductions split |
| AreaChart.vue | Canvas API | Monte Carlo 3-band forecast |
| GaugeChart.vue | Inline SVG | Payroll efficiency index |
| HeatMap.vue | CSS Grid + SVG | Attendance calendar view |
| ProgressBar.vue | CSS only | Loan repayment, AR health score |
| FunnelChart.vue | Inline SVG | Revenue funnel (booked → paid) |
| SparkLine.vue | Canvas API | Mini trend in metric cards |

### 12.2 Pinia Store Structure

```
useAuthStore        — token, user, role, login(), logout()
usePayrollStore     — salary months, current month processing
useAnalyticsStore   — all KPI data, algorithm outputs
useNotificationStore — unread count, notification list
useLoanStore        — admin: all loans | employee: own loans
useEmployeeStore    — employee list (admin), own profile (employee)
```

### 12.3 Real-Time (Pusher + Laravel Echo)

| Event Channel | Trigger | Recipient |
|---|---|---|
| salary.processed | Admin processes salary | Employee personal channel |
| salary.paid | Admin marks salary paid | Employee personal channel |
| loan.applied | Employee submits loan | Admin broadcast channel |
| loan.approved | Admin approves loan | Employee personal channel |
| loan.rejected | Admin rejects loan | Employee personal channel |
| leave.applied | Employee applies for leave | Admin broadcast channel |
| leave.decision | Admin approves/rejects leave | Employee personal channel |
| invoice.overdue | Invoice past due date | Admin broadcast channel |
| liability.due_soon | Liability due in 7 days | Admin broadcast channel |

---

## 13. API SPECIFICATION

### 13.1 Authentication Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | /api/auth/login | Login with email + passkey |
| POST | /api/auth/logout | Revoke Sanctum token |
| POST | /api/auth/change-passkey | Change own passkey (requires current) |

### 13.2 Admin — Employee Management

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/admin/employees | List all employees with pagination |
| POST | /api/admin/employees | Create employee + generate passkey |
| GET | /api/admin/employees/{id} | Get full employee details |
| PUT | /api/admin/employees/{id} | Update employee profile/salary |
| DELETE | /api/admin/employees/{id} | Soft-delete employee |
| POST | /api/admin/employees/{id}/reset-passkey | Generate new passkey |

### 13.3 Admin — Payroll

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/admin/payroll/{month} | All salary records for given month |
| POST | /api/admin/payroll/process | Process salary for employee + month |
| POST | /api/admin/payroll/bulk-process | Process entire month for all employees |
| PUT | /api/admin/payroll/{id} | Override individual salary line items |
| POST | /api/admin/payroll/{id}/mark-paid | Mark salary as paid |

### 13.4 Analytics Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | /api/admin/analytics/overview | All KPI metrics for dashboard |
| GET | /api/admin/analytics/cmgr | CMGR for all metrics |
| GET | /api/admin/analytics/forecast | Monte Carlo 3-month forecast |
| GET | /api/admin/analytics/anomalies | Z-score anomaly report |
| GET | /api/admin/analytics/burn-rate | Burn rate + runway calculation |
| GET | /api/admin/analytics/ar-health | AR health score breakdown |
| GET | /api/admin/analytics/growth | Full growth analytics data |

---

## 14. SECURITY & NON-FUNCTIONAL REQUIREMENTS

### 14.1 Security Requirements

| Requirement | Implementation |
|---|---|
| Authentication | Laravel Sanctum SPA tokens — 8h expiry |
| Passkey Security | bcrypt cost 12 — shown once — never retrievable |
| API Authorization | All routes behind auth:sanctum middleware |
| Role Enforcement | Custom middleware per route group (admin/employee) |
| Data Ownership | OwnershipCheck middleware — employees see own data only |
| SQL Injection | Eloquent ORM — parameterized queries — no raw SQL |
| XSS Prevention | Vue.js auto-escaping of all rendered data |
| Rate Limiting | 5/min login, 60/min API — Laravel throttle |
| HTTPS | ForceHttps middleware in production |
| Audit Trail | Every write → audit_logs with IP + user agent |

### 14.2 Non-Functional Requirements

| Metric | Target |
|---|---|
| Page Load Time | < 2 seconds (with API caching) |
| API Response Time | < 300ms at P95 percentile |
| Payroll Processing | < 5 seconds (async queue) |
| Analytics Recompute | < 30 seconds (background job) |
| System Uptime | 99.5% or better |
| Concurrent Users | 100+ simultaneous users supported |
| Mobile Responsive | Full tablet + mobile support (TailwindCSS) |
| Browser Support | Chrome 90+, Firefox 88+, Safari 14+ |

---

## 15. DEVELOPMENT ROADMAP

| Phase | Duration | Deliverables |
|---|---|---|
| Phase 1: Foundation | Weeks 1-3 | Laravel setup, MySQL schema, migrations, seeders, authentication (passkey), Vue.js SPA setup, Router, Pinia, TailwindCSS v4, Login page, Admin + Employee layouts |
| Phase 2: Core HR & Payroll | Weeks 4-6 | PayrollService with full calculation engine, Salary month management, PDF payslip generation, Employee portal salary history, Attendance tracking, Leave management |
| Phase 3: Financial Modules | Weeks 7-9 | Client & Project management, Invoice lifecycle with revenue recognition, Liability & amortization engine, Expense tracking + recurring expenses, Asset registry + depreciation scheduler |
| Phase 4: Loan System | Weeks 10-11 | Employee loan application portal, Admin approval/rejection flow with Pusher notifications, Auto EMI deduction in payroll, Loan repayment ledger and schedule |
| Phase 5: Real-Time & Notifications | Week 12 | Full Pusher + Laravel Echo integration, All 9 real-time event types, Notification bell component with unread count, Complete audit log system |
| Phase 6: Analytics & Reporting | Weeks 13-15 | All 6 algorithms (EMA, Z-score, Linear Regression, Monte Carlo, Efficiency Index, AR Health), CMGR engine + growth dashboard, All custom chart components, P&L + Tax + AR Aging reports |
| Phase 7: Polish & Launch | Weeks 16-17 | PDF export for all reports and payslips, System settings module, Performance optimization (query caching, index tuning), Security audit, Full end-to-end testing |

---

## 16. EMPLOYEE–ADMIN MESSAGE & QUERY SYSTEM

### 16.1 Overview

Employees can raise formal queries or appeals directly to the admin through a structured in-system messaging module. Every message is typed (late attendance appeal, deduction dispute, general HR query, etc.), tracked with status, and resolved with an admin reply. This creates a transparent, auditable paper trail replacing informal WhatsApp/email channels.

### 16.2 Message Types

| Type Code | Label | Description |
|---|---|---|
| late_appeal | Late Attendance Appeal | Employee explains reason for late arrival and requests the late mark be excused |
| deduction_dispute | Deduction Dispute | Employee challenges a deduction amount on their payslip (leave, penalty, etc.) |
| leave_clarification | Leave Policy Query | Questions about remaining leave balance, leave type eligibility |
| salary_query | Salary Query | Discrepancy or confusion about any salary line item |
| loan_query | Loan Query | Questions about loan repayment schedule, balance, or eligibility |
| general_hr | General HR Query | Any other HR-related question not covered above |

### 16.3 Database Tables

**TABLE: employee_messages**

```sql
CREATE TABLE employee_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id BIGINT UNSIGNED NOT NULL,
  thread_id BIGINT UNSIGNED NULL,  -- groups replies into a thread
  type ENUM('late_appeal','deduction_dispute','leave_clarification',
            'salary_query','loan_query','general_hr') NOT NULL,
  subject VARCHAR(300) NOT NULL,
  body TEXT NOT NULL,
  reference_date DATE NULL,        -- the attendance/payslip date in question
  reference_month DATE NULL,       -- the salary month in question
  attachments JSON NULL,           -- future: file references
  status ENUM('open','under_review','resolved','rejected') DEFAULT 'open',
  priority ENUM('normal','high') DEFAULT 'normal',
  admin_reply TEXT NULL,
  replied_by BIGINT UNSIGNED NULL, -- admin user_id
  replied_at TIMESTAMP NULL,
  action_taken ENUM('none','deduction_reversed','mark_excused',
                    'salary_adjusted','noted') DEFAULT 'none',
  resolved_at TIMESTAMP NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**TABLE: message_reads**

```sql
CREATE TABLE message_reads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  read_at TIMESTAMP NOT NULL,
  UNIQUE KEY uniq_msg_user (message_id, user_id)
);
```

### 16.4 Message Lifecycle & Status Flow

| Status | Meaning | Who Sets It |
|---|---|---|
| open | Just submitted — admin has not viewed it yet | System (on submit) |
| under_review | Admin has opened and is reviewing the message | Auto-set on admin first read |
| resolved | Admin replied and marked the issue resolved | Admin |
| rejected | Query invalid or not actionable; admin explains why | Admin |

### 16.5 Late Appeal & Action Flow

When an employee submits a late_appeal message, the admin can take a concrete system-level action directly from the message reply panel:

| Action Taken Value | System Effect |
|---|---|
| mark_excused | Sets attendances.is_late = 0 for that date; late_entries count decremented; payroll recalculated if month not yet paid |
| deduction_reversed | Finds the salary_months record for that month; zeroes the late_penalty_deduction; recomputes net_payable; creates audit log entry |
| noted | No system change; admin reply is informational only |
| none | Default — admin replied but took no payroll action |

### 16.6 Pusher Events for Messaging

| Event | Trigger | Recipient |
|---|---|---|
| message.new | Employee submits message | Admin broadcast channel — unread badge increments |
| message.replied | Admin submits reply | Employee private channel — notification + inbox badge |
| message.resolved | Admin marks resolved | Employee private channel — message card updates status |
| message.action_taken | Admin excuses late / reverses deduction | Employee private channel — payslip refresh prompt |

### 16.7 Employee Portal — Inbox View

- Tab: My Messages — list of all submitted messages with status badges
- Compose button: opens form with type selector, subject, body, optional reference date/month
- Message card shows: type label (color-coded), subject, submitted date, status badge, admin reply (if any)
- Unread reply count badge on the Inbox nav item
- Filter by type and status
- Employee cannot delete or edit a submitted message — audit integrity

### 16.8 Admin Panel — Message Center

- Queue view: all open messages sorted by submitted date (oldest first)
- Tabs: Open | Under Review | Resolved | Rejected
- Filter by: employee name, message type, date range
- Unread count badge on sidebar nav item — real-time via Pusher
- Message detail panel: full body + context card (linked payslip or attendance record)
- Reply box: text area + action_taken dropdown (for late_appeal and deduction_dispute types)
- One-click actions: Mark Resolved, Mark Rejected (with mandatory reason)
- Bulk mark-as-read for notification clearing

### 16.9 API Endpoints — Message System

| Method | Endpoint | Role | Description |
|---|---|---|---|
| POST | /api/employee/messages | Employee | Submit a new message/query |
| GET | /api/employee/messages | Employee | List own messages with status |
| GET | /api/employee/messages/{id} | Employee | View single message + admin reply |
| GET | /api/admin/messages | Admin | All messages, filterable by type/status/employee |
| GET | /api/admin/messages/{id} | Admin | View full message detail + linked payroll context |
| POST | /api/admin/messages/{id}/reply | Admin | Submit reply + set action_taken + update status |
| POST | /api/admin/messages/{id}/resolve | Admin | Mark resolved (with or without reply) |
| POST | /api/admin/messages/{id}/reject | Admin | Reject with mandatory reason |

---

## 17. LATE ATTENDANCE POLICY & PER-EMPLOYEE WORK SCHEDULE

### 17.1 Configurable Weekly Working Days (Per Employee)

Every employee can have a different weekly working day schedule set by admin. This reflects real-world scenarios where developers may work 5 days, part-time staff 3 days, and fieldwork employees 6 days. This directly affects: daily rate calculation, unpaid leave deduction, monthly working day expectations, and attendance percentage.

**Database Change: employees table — additional columns**

```sql
ALTER TABLE employees ADD COLUMN working_days_per_week TINYINT NOT NULL DEFAULT 5;
-- Valid range: 1 to 7
-- Set by admin per employee
-- Used by PayrollService to compute expected working days per month

ALTER TABLE employees ADD COLUMN weekly_off_days JSON NULL;
-- e.g. ["friday", "saturday"] for 5-day employees
-- e.g. ["friday"] for 6-day employees
-- Used by attendance module to auto-mark weekly off days
```

### 17.2 Expected Working Days Calculation (Per Employee Per Month)

```
EXPECTED_WORKING_DAYS = days_in_month
  MINUS count of weekly_off_days occurrences in that calendar month
  MINUS count of public holidays in that month

Example: Employee with weekly_off_days = ['friday','saturday']
June 2025 (30 days): 8 Fridays + 8 Saturdays = 16 off days
EXPECTED = 30 - 16 = 14 working days + minus public holidays

DAILY_RATE = gross_salary / EXPECTED_WORKING_DAYS
(NOT divided by calendar days — reflects actual contracted days)
```

### 17.3 Admin: Set Work Schedule for Employee

| UI Element | Description |
|---|---|
| Working Days/Week dropdown | Select 1 through 7 — sets working_days_per_week |
| Weekly Off Days checkboxes | Saturday, Friday, Sunday, etc. — sets weekly_off_days JSON array |
| Effective From date | Schedule change takes effect from this month onwards (history preserved) |
| Preview panel | Shows computed expected days for current and next month instantly on change |

### 17.4 Late Attendance Deduction Policy

The system enforces a clear, configurable late deduction rule: every 2 late days = 1 day salary deducted. This is distinct from the general late_threshold_days and is applied after attendance is finalized for the month.

**Policy Formula**

```
LATE_DAYS_THIS_MONTH = count of attendances WHERE is_late = 1
(excluding any excused late entries from approved late appeals)

FULL_LATE_DEDUCTION_UNITS = FLOOR(LATE_DAYS_THIS_MONTH / 2)
-- Each 2 confirmed late days = 1 full day deduction unit

LATE_DEDUCTION_AMOUNT = DAILY_RATE x FULL_LATE_DEDUCTION_UNITS

Example:
Employee was late 5 days in June
FLOOR(5 / 2) = 2 full-day deduction units
Daily rate = 45000 / 22 = 2045.45
LATE_DEDUCTION = 2045.45 x 2 = 4090.90 BDT deducted
1 remaining late day carries no deduction (needs another late day to trigger)
```

**Excused Late Days (from Approved Appeals)**

```
If admin approves a late_appeal message with action_taken = 'mark_excused':
-> attendances.is_late set to 0 for that specific date
-> LATE_DAYS_THIS_MONTH recalculated excluding excused days
-> If salary already processed: late_penalty_deduction adjusted, net_payable updated
-> Audit log entry created: 'late.excused' with admin_id, date, old/new values
```

### 17.5 Late Policy Configuration (Admin Settings)

| Setting | Default | Description |
|---|---|---|
| Late days per deduction unit | 2 | How many late days trigger 1 day deduction (the 2:1 rule) |
| Deduction unit type | full_day | Whether each unit = full day or half day salary |
| Grace period (minutes) | 15 | Minutes past office start before marking late (e.g. 9:15 AM = late) |
| Office start time | 09:00 | Configurable per company (global setting) |
| Carry-forward | No | Whether unmatched late day (odd remainders) carries to next month |

### 17.6 Updated PayrollService Logic (With New Rules)

```php
// PayrollService.php — updated processMonth()
$expectedDays = $this->getExpectedWorkingDays($emp, $month);
$dailyRate = $gross / $expectedDays;

// Late deduction: 2 late days = 1 day deduction
$lateDaysCount = $this->getConfirmedLateDays($emp->id, $month);
$lateUnits = floor($lateDaysCount / $latePolicy->days_per_unit);
$latePenalty = $dailyRate * $lateUnits;
// (deduction_unit_type: full_day = 1.0, half_day = 0.5)

// Unpaid leave deduction
$unpaidLeaves = $this->getUnpaidLeaveDays($emp->id, $month);
$leaveDeduct = $dailyRate * $unpaidLeaves;
```

### 17.7 Attendance Summary Widget (Employee Portal)

| Metric Shown | Calculation |
|---|---|
| Expected Working Days | Calendar days minus weekly offs minus holidays |
| Days Present | Count of attendances.status = 'present' |
| Days Absent | Expected - Present (auto-derived) |
| Late Entries | Count of is_late = 1 (with excused filtered out) |
| Late Deduction Applied | FLOOR(late_days / 2) x daily_rate — shown in BDT |
| Remaining Late Budget | How many more late days before next deduction triggers |

The 'Remaining Late Budget' metric is a proactive transparency feature: if an employee has been late 1 day, they see '1 more late day will trigger a 1-day salary deduction' — reducing disputes and encouraging self-awareness.

### 17.8 Public Holidays Table

```sql
CREATE TABLE public_holidays (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  date DATE NOT NULL UNIQUE,
  is_optional TINYINT(1) DEFAULT 0,  -- optional holidays don't affect all employees
  created_at TIMESTAMP
);
```

Admin manages the holiday calendar from Settings. Expected working days calculation uses this table per month. Optional holidays can be applied per-department.

---

## 18. UPDATED DEVELOPMENT ROADMAP DELTA

The following items are added to the existing 7-phase roadmap:

| Phase | New Additions |
|---|---|
| Phase 2 (HR & Payroll) | Per-employee work schedule setup, weekly_off_days JSON config, expected working days calculation, public holidays table and admin calendar |
| Phase 2 (HR & Payroll) | Updated PayrollService: daily rate uses expected_working_days, late deduction rule (2 late = 1 day), excused late days exclusion |
| Phase 5 (Real-time) | 4 new Pusher events: message.new, message.replied, message.resolved, message.action_taken |
| Phase 5 (Real-time) | Message & Query system: employee_messages table, full CRUD, admin reply panel with action_taken, Employee inbox UI, Admin Message Center |
| Phase 7 (Polish) | Late budget transparency widget in employee portal, attendance summary with deduction preview, policy configuration panel in admin settings |

---

*— End of SRS Document — FinERP v2.0 — Laravel 11 · Vue.js 3 · TailwindCSS v4 · MySQL 8 · Pusher —*
