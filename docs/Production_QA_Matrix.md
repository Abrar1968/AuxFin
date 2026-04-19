# FinERP Production QA Matrix

This matrix defines section-wise test input and expected frontend-facing output.

## Execution Order

1. `php artisan optimize:clear`
2. `php artisan migrate:fresh --seed`
3. `php artisan tinker --execute="require base_path('scripts/qa/production_matrix_runner.php');"`
4. `npm run test`
5. `npm run build`

## Coverage Rules

- Inputs are inserted through DB tinker runner (`scripts/qa/production_matrix_runner.php`).
- Validation is done against the same API contracts consumed by Vue views.
- Any mismatch is treated as logic regression.

## Case Matrix

| Case ID | Section | Tinker Input | Frontend/API Verification | Expected Output |
| --- | --- | --- | --- | --- |
| AUTH-001 | Auth | Seeded admin credentials | `POST /api/auth/login` | `200`, token returned, role `admin` |
| AUTH-002 | Auth | Seeded employee credentials | `POST /api/auth/login` | `200`, token returned, role `employee` |
| AUTH-003 | Auth Edge | Invalid passkey | `POST /api/auth/login` | `422` invalid credentials |
| RBAC-001 | Access Control Edge | Employee token | `GET /api/admin/settings/general` | `403` forbidden |
| DEPT-001 | Departments | QA department seeded | `GET /api/admin/departments?search=QA Matrix` | Department row present |
| EMP-001 | Employees | QA employee seeded with bank account | `GET /api/admin/employees?search=qa.matrix.employee` | Row present with matching email |
| EMP-002 | Employee Detail | QA employee record | `GET /api/admin/employees/{id}` | `masked_bank_account` present, raw account hidden |
| ATTN-001 | Attendance | 3 monthly records (present/late/absent) | `GET /api/admin/attendance` | Records returned, summary reflects late entry |
| ATTN-002 | Attendance Detail | Attendance record id | `GET /api/admin/attendance/{id}` | Record found with employee relation |
| ATTN-003 | Attendance Edge | Invalid status payload | `POST /api/admin/attendance` | `422` validation error |
| LEAVE-001 | Leave List | Pending + approved leave rows | `GET /api/admin/leaves?employee_id={id}` | Both rows visible |
| LEAVE-002 | Leave Decision | Pending leave | `POST /api/admin/leaves/{id}/decision` | Status transitions to approved |
| LEAVE-003 | Leave Edge | Approved leave | `DELETE /api/admin/leaves/{id}` | `422` cannot delete approved leave |
| LOAN-001 | Loan List | Pending + approved loans | `GET /api/admin/loans?status=pending` | Pending loan listed |
| LOAN-002 | Loan Detail | Pending loan id | `GET /api/admin/loans/{id}` | Loan payload and schedule returned |
| LOAN-003 | Loan Edge | Approved loan id | `DELETE /api/admin/loans/{id}` | `422` delete blocked |
| PAY-001 | Payroll Month | Processed salary month seeded | `GET /api/admin/payroll/{month}` | Salary record listed |
| PAY-002 | Payslip | Salary month id | `GET /api/admin/payroll/{id}/payslip` | Payslip payload returned |
| PAY-003 | Payroll Edge | Paid salary month id | `PUT /api/admin/payroll/{id}` | `422` paid record immutable |
| MSG-001 | Admin Messages | Open message for QA employee | `GET /api/admin/messages?employee_id={id}` | Message appears in list |
| MSG-002 | Message Show | Open message id | `GET /api/admin/messages/{id}` | Message retrievable, moves to under_review |
| MSG-003 | Message Edge | Reject without reason | `POST /api/admin/messages/{id}/reject` | `422` validation error |
| SET-001 | General Settings | QA settings update payload | `PUT + GET /api/admin/settings/general` | Returned settings match payload |
| SET-002 | Holidays | Unique holiday date | `POST /api/admin/settings/holidays` | `201` created |
| SET-003 | Holiday Edge | Duplicate holiday date | `POST /api/admin/settings/holidays` | `422` duplicate blocked |
| FIN-001 | Clients | QA client seeded | `GET /api/admin/clients?search=QA Matrix` | Client listed |
| FIN-002 | Projects | QA project + invoices seeded | `GET /api/admin/projects?client_id={id}` | Project with revenue fields |
| FIN-003 | Project Revenue | Project id | `GET /api/admin/projects/{id}/revenue` | Summary and status breakdown present |
| FIN-004 | Invoices | Paid/partial/overdue invoices seeded | `GET /api/admin/projects/{id}/invoices` | All statuses visible |
| FIN-005 | Invoice Edge | Partial transition with invalid amount | `POST /api/admin/projects/{id}/invoices/{id}/status` | `422` for invalid partial amount |
| EXP-001 | Expenses | Recurring + one-time expense seeded | `GET /api/admin/expenses` | Rows visible for month |
| EXP-002 | Expense Summary | Current month | `GET /api/admin/expenses-summary` | `monthly_total` and category totals returned |
| EXP-003 | Expense Edge | Missing recurrence data | `POST /api/admin/expenses` | `422` recurring validation |
| LIA-001 | Liabilities | Active liability due soon | `GET /api/admin/liabilities` | Liability row present |
| LIA-002 | Due Soon | Due in <=10 days | `GET /api/admin/liabilities-due-soon` | Liability returned in due-soon rows |
| LIA-003 | Liability Payment | Payment amount applied | `POST /api/admin/liabilities/{id}/process-payment` | Outstanding decreases |
| AST-001 | Assets | Active asset seeded | `GET /api/admin/assets?category=IT Equipment` | Asset row present |
| AST-002 | Asset Depreciation | Asset id | `POST /api/admin/assets/{id}/depreciate` | Book value decreases |
| AST-003 | Asset Edge | Invalid useful life update | `PUT /api/admin/assets/{id}` | `422` validation error |
| OVR-001 | Finance Overview | Seeded finance dataset | `GET /api/admin/finance/overview` | KPI and project rows returned |
| REP-001 | Profit-Loss | Monthly range | `GET /api/admin/reports/profit-loss` | Totals and rows returned |
| REP-002 | Tax Summary | Monthly range | `GET /api/admin/reports/tax-summary` | Tax totals and rate returned |
| REP-003 | AR Aging | As-of date | `GET /api/admin/reports/ar-aging` | Distribution and health score returned |
| ANA-001 | Analytics Overview | Snapshot rows seeded | `GET /api/admin/analytics/overview` | Non-empty `series` |
| ANA-002 | CMGR | Snapshot rows seeded | `GET /api/admin/analytics/cmgr` | CMGR keys present |
| ANA-003 | Forecast | Outstanding invoice buckets seeded | `GET /api/admin/analytics/forecast` | `p10`, `p50`, `p90` present |
| ANA-004 | Anomalies | Expense series seeded | `GET /api/admin/analytics/anomalies` | Array response returned |
| ANA-005 | Burn Rate | Available cash query | `GET /api/admin/analytics/burn-rate` | `cash_runway_months` returned |
| ANA-006 | AR Health | AR invoices seeded | `GET /api/admin/analytics/ar-health` | Health score/status returned |
| ANA-007 | Growth | Snapshot rows seeded | `GET /api/admin/analytics/growth` | Velocity + payroll efficiency returned |
| EDB-001 | Employee Dashboard | Employee token | `GET /api/employee/dashboard` | Salary, YTD, attendance summary |
| ESM-001 | Employee Salary List | Salary month rows seeded | `GET /api/employee/salary` | Non-empty data list |
| ESM-002 | Employee Payslip | Salary month selected | `GET /api/employee/salary/{month}/payslip` | Payslip payload returned |
| ESM-003 | Employee Payslip PDF | Salary month selected | `GET /api/employee/salary/{month}/payslip/pdf` | Filename + payload |
| ELN-001 | Employee Loans | Active loan for employee seeded | `GET /api/employee/loans` | Loan list non-empty |
| ELN-002 | Employee Loan Policy | Employee token | `GET /api/employee/loans/policy` | Policy + max amount returned |
| ELN-003 | Loan Apply Edge | Excessive amount payload | `POST /api/employee/loans/apply` | `422` policy block |
| ELV-001 | Employee Leaves | Leave row seeded | `GET /api/employee/leaves` | Leave list non-empty |
| ELV-002 | Leave Apply Edge | `to_date < from_date` | `POST /api/employee/leaves/apply` | `422` validation error |
| EAT-001 | Employee Attendance | Attendance rows seeded | `GET /api/employee/attendance` | Records + summary returned |
| EMS-001 | Employee Inbox | Message row seeded | `GET /api/employee/messages` | Message and unread_count visible |
| EMS-002 | Employee Message Detail | Message id | `GET /api/employee/messages/{id}` | Message retrievable |
| EMS-003 | Mark All Read | Inbox rows present | `POST /api/employee/messages/mark-all-read` | Success response |
| EMS-004 | Employee Message Edge | Body too short | `POST /api/employee/messages` | `422` validation error |

## Pass Criteria

- All matrix cases pass in the tinker runner output.
- No backend test failures (`npm run test`).
- No frontend build failures (`npm run build`).
- Any failed case must be fixed in code and re-run until all are green.
