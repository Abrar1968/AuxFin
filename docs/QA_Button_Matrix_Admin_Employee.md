# FinERP Button QA Matrix (Admin + Employee)

Generated on: 2026-04-16
Environment: <http://127.0.0.1:8000> (Laravel + Vue SPA)

## Sweep Summary

| Role | Total Rows | PASS | WARN | FAIL |
| --- | ---: | ---: | ---: | ---: |
| Admin | 22 | 19 | 2 | 1 |
| Employee | 16 | 15 | 1 | 0 |
| Combined | 38 | 34 | 3 | 1 |

## Admin Matrix (22 Rows)

| # | Route | Control | Status | Notes |
| ---: | --- | --- | --- | --- |
| 1 | /admin/dashboard | Sidebar: Dashboard | PASS | Route and dashboard shell loaded. |
| 2 | /admin/employees | Sidebar: Employees | PASS | Route and table shell loaded. |
| 3 | /admin/payroll | Sidebar: Payroll | FAIL | Timed out during scripted navigation in one sweep run. |
| 4 | /admin/loans | Sidebar: Loans | PASS | Route loaded and actions visible. |
| 5 | /admin/projects | Sidebar: Projects | PASS | Route loaded and actions visible. |
| 6 | /admin/expenses | Sidebar: Expenses | PASS | Route loaded and actions visible. |
| 7 | /admin/liabilities | Sidebar: Liabilities | PASS | Route loaded and actions visible. |
| 8 | /admin/assets | Sidebar: Assets | PASS | Route loaded and actions visible. |
| 9 | /admin/equity | Sidebar: Equity | PASS | Route loaded and actions visible. |
| 10 | /admin/reports | Sidebar: Reports | PASS | Route loaded and actions visible. |
| 11 | /admin/attendance | Sidebar: Attendance | PASS | Route loaded and actions visible. |
| 12 | /admin/leaves | Sidebar: Leaves | PASS | Route loaded and actions visible. |
| 13 | /admin/inbox | Sidebar: Inbox | PASS | Route loaded and actions visible. |
| 14 | /admin/settings | Sidebar: Settings | PASS | Route loaded and actions visible. |
| 15 | /admin/docs | Open Print View | PASS | Link available and opens print route. |
| 16 | /admin/docs | Download Manual PDF | PASS | Control rendered and enabled. |
| 17 | /admin/docs | Search input | PASS | Search accepted query and filtered section count. |
| 18 | /admin/docs | Scope filter (All/Sections/Equations) | WARN | Rendered and selectable; deep value-level assertions deferred. |
| 19 | /admin/docs | Reset Filters | WARN | Disabled by default until search/filter changes (expected UX). |
| 20 | /admin/docs/print | Back To Docs | PASS | Link present and points to /admin/docs. |
| 21 | /admin/docs/print | Download PDF | PASS | Control rendered and enabled. |
| 22 | /admin/docs/print | Print Now | PASS | Control rendered and enabled. |

## Employee Matrix (16 Rows)

| # | Route | Control | Status | Notes |
| ---: | --- | --- | --- | --- |
| 1 | /portal/dashboard | Sidebar: Dashboard | PASS | Route and dashboard shell loaded. |
| 2 | /portal/salary | Sidebar: My Salary | PASS | Route loaded and salary shell visible. |
| 3 | /portal/loans | Sidebar: Loans | PASS | Route loaded and actions visible. |
| 4 | /portal/leaves | Sidebar: Leaves | PASS | Route loaded and actions visible. |
| 5 | /portal/attendance | Sidebar: Attendance | PASS | Route loaded and actions visible. |
| 6 | /portal/inbox | Sidebar: Inbox | PASS | Route loaded and list shell visible. |
| 7 | /portal/docs | Sidebar: Docs Manual | PASS | Route loaded with manual content. |
| 8 | /portal/docs/print | Direct route load | PASS | Print page rendered successfully. |
| 9 | /portal/docs | Open Print View | PASS | Link available and opens print route. |
| 10 | /portal/docs | Download Manual PDF | PASS | Control rendered and enabled. |
| 11 | /portal/docs | Search input | PASS | Search/filter controls visible and interactive. |
| 12 | /portal/docs | Scope filter (All/Sections/Equations) | PASS | Scope options available in dropdown. |
| 13 | /portal/docs | Reset Filters | WARN | Disabled in default state until filters are changed (expected UX). |
| 14 | /portal/docs/print | Back To Docs | PASS | Link present and points to /portal/docs. |
| 15 | /portal/docs/print | Download PDF | PASS | Control rendered and enabled. |
| 16 | /portal/docs/print | Print Now | PASS | Control rendered and enabled. |

## Notes

- The single FAIL row is a scripted timeout for /admin/payroll, not a deterministic compile/runtime crash in this pass.
- WARN rows for Reset Filters are expected in default state because reset is intentionally disabled before any filter mutation.
- Admin docs manual search was validated with query `payroll` (counts narrowed) and reset returned counts to full set.
- Print route controls were verified on both roles:
  - /admin/docs/print: Back To Docs, Download PDF, Print Now.
  - /portal/docs/print: Back To Docs, Download PDF, Print Now.

## Rerun Verification (Latest)

| Role | Routes Checked | PASS | WARN | FAIL |
| --- | ---: | ---: | ---: | ---: |
| Admin | 18 | 18 | 0 | 0 |
| Employee | 8 | 8 | 0 | 0 |

- Rerun covered core admin and employee sidebar routes plus docs print routes.
- No fatal body markers were detected (`server error`, `whoops`, or `fatal error`).
- No console-level error events were captured by the scripted sweep.
