# FinERP — Frontend Development Guide

**Vue.js 3 (Composition API) · TailwindCSS v4 · Pinia · Pusher + Laravel Echo**

> Version 2.0 | Clean Architecture Edition | Extracted from SRS v2.0

---

## Design System

> Sourced from UI Pro Max Skill · Enterprise SaaS + Data-Dense Dashboard pattern

### Color Tokens

```css
/* globals.css — Design Tokens */
:root {
  /* Backgrounds */
  --bg-base:      #F8FAFC;   /* app background */
  --bg-surface:   #FFFFFF;   /* cards, panels */
  --bg-muted:     #F1F5F9;   /* subtle fills */
  --bg-dark:      #0F172A;   /* dark sidebar */
  --bg-dark-card: #1E293B;   /* dark card surface */

  /* Brand */
  --color-primary:   #4F46E5;  /* Indigo — primary actions */
  --color-secondary: #7C3AED;  /* Violet — gradients, accents */
  --color-gradient:  linear-gradient(135deg, #4F46E5, #7C3AED);

  /* Semantic */
  --color-success:  #10B981;   /* Emerald */
  --color-warning:  #F59E0B;   /* Amber */
  --color-danger:   #EF4444;   /* Red */
  --color-info:     #3B82F6;   /* Blue */

  /* Text */
  --text-primary: #0F172A;
  --text-muted:   #64748B;
  --text-light:   #94A3B8;

  /* Borders & Shadows */
  --border:        #E2E8F0;
  --shadow-card:   0 1px 3px rgba(79,70,229,0.08), 0 1px 2px rgba(0,0,0,0.04);
  --shadow-panel:  0 4px 6px rgba(79,70,229,0.07), 0 2px 4px rgba(0,0,0,0.05);

  /* Radius */
  --radius-sm:   6px;
  --radius-md:   10px;
  --radius-lg:   16px;
  --radius-pill: 999px;

  /* Spacing grid (8px base) */
  --space-1:  4px;
  --space-2:  8px;
  --space-3:  12px;
  --space-4:  16px;
  --space-5:  20px;
  --space-6:  24px;
  --space-8:  32px;
  --space-10: 40px;
}
```

### Typography

```css
/* Google Font: Plus Jakarta Sans (Enterprise SaaS — single family) */
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

body { font-family: 'Plus Jakarta Sans', sans-serif; }

/* Scale */
/* Hero/Page Title:   32–40px  ExtraBold 800  line-height 1.1 */
/* Section H2:        24–28px  Bold 700        line-height 1.2 */
/* Card Title:        18px     SemiBold 600    line-height 1.3 */
/* Body / Table:      14–16px  Regular 400     line-height 1.5 */
/* Label / Badge:     11–12px  SemiBold 600    tracking 0.5px uppercase */
/* Monospace data:    JetBrains Mono 12px      for code, IDs */
```

---

## Table of Contents

1. [Stack & Constraints](#1-stack--constraints)
2. [Clean Architecture Overview](#2-clean-architecture-overview)
3. [Folder Structure](#3-folder-structure)
4. [Layout System](#4-layout-system)
5. [Page Layouts — Admin](#5-page-layouts--admin)
6. [Page Layouts — Employee Portal](#6-page-layouts--employee-portal)
7. [Component Library](#7-component-library)
8. [Custom Chart Components](#8-custom-chart-components)
9. [Pinia Store Architecture](#9-pinia-store-architecture)
10. [Composables](#10-composables)
11. [API Layer (Services)](#11-api-layer-services)
12. [Real-Time (Pusher + Laravel Echo)](#12-real-time-pusher--laravel-echo)
13. [Router & Route Guards](#13-router--route-guards)
14. [All Views Reference](#14-all-views-reference)
15. [Development Roadmap](#15-development-roadmap)

---

## 1. Stack & Constraints

> **Strict — No Deviations Permitted**

| Technology | Version / Constraint |
|---|---|
| Framework | Vue.js 3 — Composition API only (no Options API) |
| Styling | TailwindCSS v4 — No external UI libraries (no PrimeVue, Vuetify, etc.) |
| State Management | Pinia — No Vuex |
| Charts | Custom Canvas/SVG components — No chart libraries (no Chart.js, ECharts) |
| HTTP Client | Axios — configured with Sanctum token interceptor |
| Real-time | Pusher + Laravel Echo |
| Build Tool | Vite |
| TypeScript | Optional but recommended for stores and services |

---

## 2. Clean Architecture Overview

FinERP frontend follows a strict **separation of concerns** across 5 layers:

```
┌─────────────────────────────────────────────────────────┐
│  VIEWS (Pages)                                           │
│  Thin components — orchestrate data, no business logic   │
├─────────────────────────────────────────────────────────┤
│  COMPONENTS (UI Layer)                                   │
│  Reusable, stateless, purely presentational              │
├─────────────────────────────────────────────────────────┤
│  COMPOSABLES (Business Logic Layer)                      │
│  usePayroll, useAnalytics, useLoan — encapsulate logic   │
├─────────────────────────────────────────────────────────┤
│  STORES (State Layer — Pinia)                            │
│  Global reactive state — never called directly by views  │
├─────────────────────────────────────────────────────────┤
│  SERVICES (API Layer)                                    │
│  All HTTP calls — views never import axios directly      │
└─────────────────────────────────────────────────────────┘
```

### Architecture Rules

- Views NEVER import `axios` directly — always use a Service
- Views NEVER contain business logic — delegate to Composables
- Components NEVER import Stores — receive data via props
- Stores NEVER call the API directly — call Services instead
- Composables bridge Stores ↔ Services and expose reactive data to Views

---

## 3. Folder Structure

```
src/
├── main.ts                         ← App bootstrap, plugins
├── router/
│   ├── index.ts                    ← Route definitions
│   └── guards.ts                   ← Auth & role guards
│
├── layouts/
│   ├── AdminLayout.vue             ← Sidebar + topbar shell (admin)
│   ├── EmployeeLayout.vue          ← Sidebar + topbar shell (employee)
│   └── AuthLayout.vue              ← Centered card shell (login)
│
├── views/
│   ├── auth/
│   │   └── LoginView.vue
│   ├── admin/
│   │   ├── DashboardView.vue       ← Main admin dashboard
│   │   ├── employees/
│   │   │   ├── EmployeeListView.vue
│   │   │   ├── EmployeeCreateView.vue
│   │   │   └── EmployeeDetailView.vue
│   │   ├── payroll/
│   │   │   ├── PayrollView.vue
│   │   │   └── PayslipView.vue
│   │   ├── loans/
│   │   │   └── LoanManagementView.vue
│   │   ├── projects/
│   │   │   ├── ProjectListView.vue
│   │   │   └── InvoiceView.vue
│   │   ├── expenses/
│   │   │   └── ExpenseView.vue
│   │   ├── liabilities/
│   │   │   └── LiabilityView.vue
│   │   ├── assets/
│   │   │   └── AssetView.vue
│   │   ├── analytics/
│   │   │   ├── AnalyticsOverviewView.vue
│   │   │   └── GrowthDashboardView.vue
│   │   ├── messages/
│   │   │   └── MessageCenterView.vue
│   │   └── settings/
│   │       └── SettingsView.vue
│   └── employee/
│       ├── DashboardView.vue       ← Employee portal home
│       ├── salary/
│       │   ├── SalaryHistoryView.vue
│       │   └── PayslipDetailView.vue
│       ├── loans/
│       │   ├── LoanStatusView.vue
│       │   └── LoanApplyView.vue
│       ├── leaves/
│       │   └── LeaveView.vue
│       ├── attendance/
│       │   └── AttendanceView.vue
│       └── messages/
│           └── InboxView.vue
│
├── components/
│   ├── layout/
│   │   ├── Sidebar.vue
│   │   ├── Topbar.vue
│   │   ├── NotificationBell.vue
│   │   └── BreadcrumbNav.vue
│   ├── ui/
│   │   ├── AppCard.vue             ← Base card container
│   │   ├── KpiCard.vue             ← Metric card with sparkline
│   │   ├── StatusBadge.vue         ← Pill badge (paid/draft/pending)
│   │   ├── AppTable.vue            ← Sortable data table
│   │   ├── AppModal.vue            ← Dialog overlay
│   │   ├── AppButton.vue           ← Primary/secondary/ghost variants
│   │   ├── AppInput.vue            ← Floating label input
│   │   ├── AppSelect.vue           ← Dropdown select
│   │   ├── AppPagination.vue
│   │   ├── AppAlert.vue            ← Success / error / warning
│   │   ├── LoadingSpinner.vue
│   │   └── SkeletonLoader.vue
│   ├── charts/
│   │   ├── BarChart.vue            ← Canvas API
│   │   ├── LineChart.vue           ← Canvas API (with EMA overlay)
│   │   ├── DonutChart.vue          ← Inline SVG
│   │   ├── AreaChart.vue           ← Canvas API (Monte Carlo bands)
│   │   ├── GaugeChart.vue          ← Inline SVG (payroll efficiency)
│   │   ├── HeatMap.vue             ← CSS Grid + SVG (attendance)
│   │   ├── ProgressBar.vue         ← CSS only
│   │   ├── FunnelChart.vue         ← Inline SVG (revenue funnel)
│   │   └── SparkLine.vue           ← Canvas API (inline in KPI cards)
│   └── domain/
│       ├── payroll/
│       │   ├── PayslipCard.vue
│       │   └── SalaryBreakdownTable.vue
│       ├── loans/
│       │   └── LoanStatusCard.vue
│       ├── analytics/
│       │   ├── GrowthVelocityCard.vue
│       │   └── AnomalyAlert.vue
│       └── messages/
│           ├── MessageCard.vue
│           └── MessageComposeForm.vue
│
├── stores/
│   ├── auth.store.ts               ← useAuthStore
│   ├── payroll.store.ts            ← usePayrollStore
│   ├── analytics.store.ts          ← useAnalyticsStore
│   ├── notification.store.ts       ← useNotificationStore
│   ├── loan.store.ts               ← useLoanStore
│   └── employee.store.ts           ← useEmployeeStore
│
├── composables/
│   ├── usePayroll.ts
│   ├── useAnalytics.ts
│   ├── useLoan.ts
│   ├── useEmployee.ts
│   ├── useAttendance.ts
│   ├── useMessages.ts
│   └── useRealTime.ts              ← Pusher + Echo subscription manager
│
├── services/
│   ├── api.service.ts              ← Axios instance + interceptors
│   ├── auth.service.ts
│   ├── employee.service.ts
│   ├── payroll.service.ts
│   ├── analytics.service.ts
│   ├── loan.service.ts
│   ├── leave.service.ts
│   ├── attendance.service.ts
│   └── message.service.ts
│
├── utils/
│   ├── formatters.ts               ← currency, date, percentage formatters
│   ├── validators.ts               ← form validation helpers
│   └── constants.ts                ← enums, magic values
│
└── types/
    ├── auth.types.ts
    ├── employee.types.ts
    ├── payroll.types.ts
    ├── analytics.types.ts
    └── loan.types.ts
```

---

## 4. Layout System

### 4.1 AdminLayout.vue — Shell Structure

```
┌──────────────────────────────────────────────────────────┐
│  TOPBAR (h-16, sticky)                                   │
│  [☰ Menu]  FinERP Logo         [🔔 Bell (N)]  [Avatar]  │
├────────────────┬─────────────────────────────────────────┤
│                │                                         │
│  SIDEBAR       │  MAIN CONTENT AREA                      │
│  (w-64)        │  (flex-1, overflow-y-auto)              │
│                │                                         │
│  ● Dashboard   │  <router-view />                        │
│  ● Employees   │                                         │
│  ● Payroll     │                                         │
│  ● Loans       │                                         │
│  ● Projects    │                                         │
│  ● Expenses    │                                         │
│  ● Liabilities │                                         │
│  ● Assets      │                                         │
│  ─────────     │                                         │
│  ● Analytics   │                                         │
│  ● Growth      │                                         │
│  ─────────     │                                         │
│  ● Messages  N │                                         │
│  ● Settings    │                                         │
│  ─────────     │                                         │
│  ● Audit Log   │                                         │
│                │                                         │
└────────────────┴─────────────────────────────────────────┘
```

```vue
<!-- layouts/AdminLayout.vue -->
<template>
  <div class="flex h-screen bg-[--bg-base] overflow-hidden">
    <!-- Sidebar -->
    <Sidebar :collapsed="sidebarCollapsed" role="admin" />

    <div class="flex flex-col flex-1 overflow-hidden">
      <!-- Topbar -->
      <Topbar @toggle-sidebar="sidebarCollapsed = !sidebarCollapsed" />

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-6">
        <BreadcrumbNav />
        <router-view />
      </main>
    </div>
  </div>
</template>
```

### 4.2 Sidebar Design Spec

```
Background:     #0F172A (dark navy)
Width:          256px (collapsed: 64px)
Logo area:      h-16, flex items-center px-6
Nav item:       h-10, px-3, rounded-lg, flex items-center gap-3
Nav item text:  14px SemiBold, color #94A3B8 (muted)
Active item:    background rgba(79,70,229,0.15), text #4F46E5, icon #4F46E5
Hover item:     background rgba(255,255,255,0.05), text #E2E8F0
Section label:  11px UPPERCASE tracking-wider, color #475569, px-3 mb-1
Badge (N):      Indigo pill, 18px min-width, text white 11px SemiBold
Bottom:         User avatar + name + logout button
```

### 4.3 Topbar Design Spec

```
Background:     #FFFFFF with border-b #E2E8F0
Height:         64px (h-16)
Left:           Hamburger toggle (admin) | Logo (mobile)
Center:         Page title (dynamic, 20px SemiBold)
Right:          [Notification Bell] [Avatar Dropdown]

Notification Bell:
  - Icon: outline bell, 22px, color #64748B
  - Badge: red circle, absolute top-right, 14px font
  - Dropdown: 380px wide, max-h-96 scrollable, items with time stamps
  - Real-time: unread count updates via Pusher event

Avatar Dropdown:
  - Avatar: 36px circle with initials or photo
  - Options: My Profile | Change Passkey | Logout
```

### 4.4 EmployeeLayout.vue — Shell Structure

```
┌──────────────────────────────────────────────────────────┐
│  TOPBAR (h-16, sticky)                                   │
│  [☰]  FinERP Employee Portal        [🔔 Bell]  [Avatar] │
├───────────────┬──────────────────────────────────────────┤
│               │                                          │
│  SIDEBAR      │  MAIN CONTENT AREA                       │
│  (w-56)       │                                          │
│               │  <router-view />                         │
│  ● Home       │                                          │
│  ● My Salary  │                                          │
│  ● Loans      │                                          │
│  ● Leaves     │                                          │
│  ● Attendance │                                          │
│  ● Messages N │                                          │
│               │                                          │
└───────────────┴──────────────────────────────────────────┘
```

### 4.5 AuthLayout.vue — Login Shell

```
Full viewport centered layout:
  Background:   Indigo → Violet gradient (135deg)
  Card:         white, 480px max-width, 40px padding, 16px radius
  Shadow:       0 25px 50px rgba(0,0,0,0.15)
  Logo:         Centered above card, 48px mark + wordmark
```

---

## 5. Page Layouts — Admin

### 5.1 Admin Dashboard (DashboardView.vue)

```
LAYOUT: Single column with responsive grid sections

┌─────────────────────────────────────────────────────────┐
│  PAGE HEADER                                            │
│  "Dashboard"  ← h1 Bold 32px                           │
│  "April 2025" ← subtitle muted 16px          [Actions]  │
├─────────────────────────────────────────────────────────┤
│  KPI ROW — 4 cards (grid-cols-4, gap-6)                 │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌─────────┐ │
│  │ Revenue   │ │ Payroll   │ │ Net Profit│ │ AR      │ │
│  │ ৳8.4M     │ │ ৳2.1M    │ │ ৳1.8M    │ │ ৳3.2M  │ │
│  │ ▲ 12.4%   │ │ ▲ 5.1%   │ │ ▲ 18.7%  │ │ ▼ 4.2% │ │
│  │ [sparkline]│ │[sparkline]│ │[sparkline]│ │[sparkln]│ │
│  └───────────┘ └───────────┘ └───────────┘ └─────────┘ │
├─────────────────────────────────────────────────────────┤
│  ROW 2 — Charts (grid-cols-3, gap-6)                    │
│  ┌──────────────────────────┐ ┌────────────────────────┐│
│  │  Revenue vs Payroll      │ │  Payroll Efficiency    ││
│  │  BarChart.vue            │ │  GaugeChart.vue        ││
│  │  (col-span-2)            │ │  Ratio + zones         ││
│  └──────────────────────────┘ └────────────────────────┘│
├─────────────────────────────────────────────────────────┤
│  ROW 3 — Tables (grid-cols-2, gap-6)                    │
│  ┌─────────────────────────┐ ┌──────────────────────────┐│
│  │  Recent Invoices         │ │  Pending Loans           ││
│  │  AppTable.vue            │ │  AppTable.vue            ││
│  │  + status badges         │ │  + approve/reject CTA    ││
│  └─────────────────────────┘ └──────────────────────────┘│
├─────────────────────────────────────────────────────────┤
│  ROW 4 — Anomaly Alerts                                 │
│  AnomalyAlert.vue — Z-score flagged items in amber cards │
└─────────────────────────────────────────────────────────┘
```

### 5.2 Analytics Overview (AnalyticsOverviewView.vue)

```
┌────────────────────────────────────────────────────────┐
│  PAGE HEADER + date range filter                        │
├──────────────────────────────┬─────────────────────────┤
│  Revenue Trend               │  Cash Flow Forecast      │
│  LineChart.vue               │  AreaChart.vue           │
│  (EMA overlay, 12mo)         │  (Monte Carlo 3-band)    │
├──────────────────────────────┴─────────────────────────┤
│  AR Health Score     AR Aging Table    Funnel Chart     │
│  ProgressBar.vue     AppTable.vue      FunnelChart.vue  │
│  (score + label)     (0-30,31-60...)   (booked→paid)   │
└────────────────────────────────────────────────────────┘
```

### 5.3 Growth Dashboard (GrowthDashboardView.vue)

```
┌────────────────────────────────────────────────────────┐
│  "Company Growth Analytics"           Period selector   │
├────────────────────────────────────────────────────────┤
│  CMGR VELOCITY CARDS (grid-cols-3 → grid-cols-6)       │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ...              │
│  │ Revenue │ │Headcount│ │ Payroll │                   │
│  │  CMGR   │ │  CMGR   │ │  CMGR   │                   │
│  │ +8.4%   │ │ +4.2%   │ │ +5.1%   │                   │
│  │ ↑ trend │ │ ↑ trend │ │ ↑ trend │                   │
│  └─────────┘ └─────────┘ └─────────┘                   │
├────────────────────────────────────────────────────────┤
│  Growth Rate Chart (LineChart — 6mo CMGR)              │
│  3 lines: Revenue vs Payroll vs OpEx CMGR              │
├─────────────────────┬──────────────────────────────────┤
│  Growth Efficiency  │  Revenue Quality Score           │
│  Ratio gauge        │  ProgressBar — 80%+ green        │
├─────────────────────┴──────────────────────────────────┤
│  Cash Runway Calculator — input + computed output       │
│  Burn Rate Trend — rolling 3-month average line chart   │
├────────────────────────────────────────────────────────┤
│  Cohort Comparison — H1 vs H1 side-by-side bars        │
└────────────────────────────────────────────────────────┘
```

### 5.4 Payroll View (PayrollView.vue)

```
┌────────────────────────────────────────────────────────┐
│  "Payroll — June 2025"    [Month selector]  [Bulk Run] │
├────────────────────────────────────────────────────────┤
│  Summary Bar: Total Gross | Total Deductions | Net Total│
├────────────────────────────────────────────────────────┤
│  Employee payroll table — sortable, paginated           │
│  Cols: Name | Department | Gross | Deductions | Net     │
│        | Status | Actions                               │
│  Row expand → full payslip breakdown inline             │
│  Actions: [Process] [Mark Paid] [View Payslip] [PDF]    │
└────────────────────────────────────────────────────────┘
```

### 5.5 Employee Management (EmployeeListView.vue)

```
┌────────────────────────────────────────────────────────┐
│  "Employees"                            [+ Add Employee]│
├────────────────────────────────────────────────────────┤
│  Filter row: Dept | Status | Search field               │
├────────────────────────────────────────────────────────┤
│  Table: Code | Name | Dept | Designation | Salary       │
│         | Status | Actions [View] [Edit] [Reset Key]    │
│  Pagination: 20 per page                                │
└────────────────────────────────────────────────────────┘

Add Employee Modal (AppModal.vue — wide, 2-column grid form):
  Col 1: Name, Email, Department, Designation, Date of Joining
  Col 2: Basic Salary, HRA, Conveyance, Medical, PF%, TDS%, Prof Tax
  Work Schedule tab: Working Days/Week, Weekly Off Days (checkboxes)
  On Save → passkey shown in success modal (copy button, one-time only)
```

### 5.6 Message Center (MessageCenterView.vue)

```
┌────────────────────────────────────────────────────────┐
│  "Message Center"                      Unread badge: 4  │
├────────────────────────────────────────────────────────┤
│  Tabs: [Open (4)] [Under Review] [Resolved] [Rejected]  │
│  Filter: Employee ▼  Message Type ▼  Date Range        │
├───────────────────────────┬────────────────────────────┤
│  MESSAGE LIST (left)      │  MESSAGE DETAIL (right)    │
│                           │                            │
│  ┌─────────────────────┐  │  Subject + Type badge      │
│  │ [Type] Subject      │  │  Employee info             │
│  │ John D.  2hrs ago   │  │  Reference: payslip/date   │
│  │ [open] badge        │  │  ─────────────────────     │
│  └─────────────────────┘  │  Full message body         │
│  ┌─────────────────────┐  │  ─────────────────────     │
│  │ [Type] Subject      │  │  Reply textarea            │
│  │ Sara M.  1d ago     │  │  Action: [dropdown]        │
│  └─────────────────────┘  │  [Mark Resolved] [Reject]  │
└───────────────────────────┴────────────────────────────┘
```

---

## 6. Page Layouts — Employee Portal

### 6.1 Employee Dashboard (DashboardView.vue)

```
┌────────────────────────────────────────────────────────┐
│  "Hello, John 👋"    "June 2025"                        │
├────────────────────────────────────────────────────────┤
│  KPI CARDS (grid-cols-2 md:grid-cols-4)                │
│  ┌─────────────────┐  ┌─────────────────┐              │
│  │ Current Month   │  │ Total Earned    │              │
│  │ Net Salary      │  │ YTD             │              │
│  │ ৳38,450         │  │ ৳2,21,500       │              │
│  │ [PAID badge]    │  │ 6 months        │              │
│  └─────────────────┘  └─────────────────┘              │
│  ┌─────────────────┐  ┌─────────────────┐              │
│  │ Total Deducted  │  │ Loan Balance    │              │
│  │ YTD             │  │ Outstanding     │              │
│  │ ৳24,300         │  │ ৳45,000         │              │
│  └─────────────────┘  └─────────────────┘              │
├────────────────────────────────────────────────────────┤
│  Charts row (grid-cols-2)                              │
│  ┌─────────────────────────┐ ┌──────────────────────┐  │
│  │ Net Salary Trend        │ │ Earnings vs Deductions│  │
│  │ BarChart.vue (12mo)     │ │ DonutChart.vue        │  │
│  └─────────────────────────┘ └──────────────────────┘  │
├────────────────────────────────────────────────────────┤
│  Attendance Summary Widget                             │
│  Expected 22 | Present 19 | Absent 3 | Late 2          │
│  ⚠ "1 more late day will trigger a salary deduction"  │
└────────────────────────────────────────────────────────┘
```

### 6.2 Salary History (SalaryHistoryView.vue)

```
┌────────────────────────────────────────────────────────┐
│  "Salary History"                                       │
├────────────────────────────────────────────────────────┤
│  Table: Month | Gross | Bonuses | Deductions | EMI | Net│
│         | Status badge | [View] [PDF Download]          │
│                                                         │
│  ▼ Row expand → PayslipCard.vue inline:                 │
│     Earnings section + Deductions section side-by-side  │
│     Net Payable highlighted in Indigo                   │
│     MoM delta shown (+5.2% vs last month)              │
└────────────────────────────────────────────────────────┘
```

### 6.3 Employee Inbox (InboxView.vue)

```
┌────────────────────────────────────────────────────────┐
│  "My Messages"              [+ Compose New Query]       │
├────────────────────────────────────────────────────────┤
│  Filter: Status ▼  Type ▼                              │
├────────────────────────────────────────────────────────┤
│  MessageCard.vue per message:                          │
│  [Type badge] Subject line               [Status badge] │
│  Submitted: 3 Jun 2025                                  │
│  Admin Reply: "Your late entry on Jun 3 has been..."   │
└────────────────────────────────────────────────────────┘

Compose Modal (MessageComposeForm.vue):
  Type selector (color-coded dropdown)
  Subject field
  Body textarea (min 3 rows)
  Reference Date (optional — for late_appeal)
  Reference Month (optional — for salary_query)
```

### 6.4 Attendance View (AttendanceView.vue)

```
┌────────────────────────────────────────────────────────┐
│  "Attendance — June 2025"      [< Prev] [Next >]       │
├────────────────────────────────────────────────────────┤
│  HeatMap.vue — calendar grid view                      │
│  Color coding:                                          │
│    Green  = present                                     │
│    Red    = absent                                      │
│    Amber  = late                                        │
│    Gray   = weekly off / holiday                        │
├────────────────────────────────────────────────────────┤
│  Summary row:                                           │
│  Expected: 22 | Present: 19 | Late: 2 | Absent: 1      │
│  Late Deduction Applied: ৳2,045.45                     │
│  ⚠ "1 more late day = 1 day deduction"                │
└────────────────────────────────────────────────────────┘
```

---

## 7. Component Library

### 7.1 KpiCard.vue

```vue
<!-- Props: title, value, delta, deltaType('up'|'down'), sparklineData -->
<template>
  <div class="bg-white rounded-[--radius-lg] p-5 shadow-[--shadow-card]">
    <p class="text-sm font-semibold text-[--text-muted] uppercase tracking-wide">
      {{ title }}
    </p>
    <p class="text-2xl font-bold text-[--text-primary] mt-1">{{ value }}</p>
    <div class="flex items-center gap-1 mt-1">
      <!-- delta arrow -->
      <span :class="deltaType === 'up' ? 'text-green-500' : 'text-red-500'">
        {{ deltaType === 'up' ? '▲' : '▼' }} {{ delta }}
      </span>
    </div>
    <SparkLine :data="sparklineData" class="mt-3" />
  </div>
</template>
```

### 7.2 StatusBadge.vue

```vue
<!-- Props: status — auto-maps to color -->
<!-- paid/active/approved → green -->
<!-- pending/draft → amber -->
<!-- rejected/overdue → red -->
<!-- processed/under_review → blue -->
<template>
  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold', colorClass]">
    {{ label }}
  </span>
</template>
```

### 7.3 AppTable.vue

```
Features:
  - Sortable column headers (emit sort event)
  - Slot-based cell rendering for custom content
  - Row click expand slot
  - Loading skeleton state
  - Empty state slot
  - Pagination via AppPagination.vue
  - Sticky header
  - Zebra row styling optional
```

### 7.4 AppModal.vue

```
Features:
  - Teleport to body
  - Focus trap
  - ESC key close
  - Slot: header, body, footer
  - Sizes: sm (480px) | md (640px) | lg (800px) | xl (1000px)
  - Backdrop blur on open
  - Slide-up animation (200ms)
```

### 7.5 AppButton.vue

```
Variants:
  primary   → Indigo→Violet gradient, white text
  secondary → white bg, Indigo border, Indigo text
  danger    → red bg, white text
  ghost     → transparent, muted text, hover bg-slate-100

Sizes: sm | md (default) | lg
States: loading (spinner replaces label) | disabled
```

---

## 8. Custom Chart Components

> **Rule: ALL charts use Canvas API or inline SVG — no external chart libraries**

### 8.1 BarChart.vue

```
Tech:      Canvas API
Props:     labels[], datasets[], height, currency(bool)
Features:
  - Responsive (ResizeObserver)
  - Hover tooltip (custom canvas tooltip)
  - Animated on mount (bars grow up, 400ms ease-out)
  - Color per dataset
  - Legend optional
Used for:  Monthly revenue, payroll, bonus trends
```

### 8.2 LineChart.vue

```
Tech:      Canvas API
Props:     labels[], datasets[], showEMA, alpha(0.3), height
Features:
  - EMA smoothing overlay as dashed line
  - Hover crosshair + data tooltip
  - Area fill under line (optional, 10% opacity)
  - Animated draw-on (path draws left to right)
Used for:  Revenue trends, CMGR over time
```

### 8.3 DonutChart.vue

```
Tech:      Inline SVG
Props:     segments[{label, value, color}], size(200), strokeWidth(32)
Features:
  - Center text: total value
  - Hover highlights segment + shows tooltip
  - Animated (stroke-dasharray transition)
  - Legend below or to the right
Used for:  Earnings vs Deductions split
```

### 8.4 AreaChart.vue

```
Tech:      Canvas API
Props:     labels[], p10[], p50[], p90[], height
Features:
  - 3-band rendering: P10 (red fill), P50 (blue line), P90 (green fill)
  - Bands filled with 15% opacity
  - Legend: Pessimistic / Base Case / Optimistic
  - Tooltip shows all 3 values at hovered point
Used for:  Monte Carlo cash flow forecast
```

### 8.5 GaugeChart.vue

```
Tech:      Inline SVG
Props:     value(0-100), label, zones[{from, to, color}]
Features:
  - Semicircle arc with colored zones
  - Animated needle rotation
  - Zone labels: Target | Warning | Critical
  - Center: value% + status label
Used for:  Payroll Cost Efficiency Index
```

### 8.6 HeatMap.vue

```
Tech:      CSS Grid + SVG
Props:     year, month, data[{date, status}]
Features:
  - Calendar month view (7×5/6 grid)
  - Color-coded cells: present(green) late(amber) absent(red) off(gray)
  - Hover tooltip: date + status + check-in time
  - Legend row at bottom
Used for:  Employee attendance calendar
```

### 8.7 SparkLine.vue

```
Tech:      Canvas API
Props:     data[], width(100), height(32), color
Features:
  - Minimal line with no axes
  - Optional area fill
  - Smooth bezier curves
  - Last point dot marker
Used for:  KPI card inline trend indicator
```

---

## 9. Pinia Store Architecture

### 9.1 useAuthStore (auth.store.ts)

```typescript
export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('fin_token'))
  const user  = ref<AuthUser | null>(null)
  const role  = computed(() => user.value?.role)

  async function login(email: string, passkey: string) { ... }
  async function logout() { ... }
  async function fetchMe() { ... }

  return { token, user, role, login, logout, fetchMe }
})
```

### 9.2 usePayrollStore (payroll.store.ts)

```typescript
export const usePayrollStore = defineStore('payroll', () => {
  const salaryMonths = ref<SalaryMonth[]>([])
  const currentMonth = ref<SalaryMonth | null>(null)
  const loading      = ref(false)

  async function fetchMonthPayroll(month: string) { ... }
  async function processEmployee(employeeId: number, month: string) { ... }
  async function bulkProcess(month: string) { ... }
  async function markPaid(id: number) { ... }

  return { salaryMonths, currentMonth, loading, fetchMonthPayroll, processEmployee, bulkProcess, markPaid }
})
```

### 9.3 useAnalyticsStore (analytics.store.ts)

```typescript
export const useAnalyticsStore = defineStore('analytics', () => {
  const overview    = ref<AnalyticsOverview | null>(null)
  const cmgr        = ref<CMGRData | null>(null)
  const forecast    = ref<MonteCarloResult | null>(null)
  const anomalies   = ref<AnomalyItem[]>([])
  const arHealth    = ref<ARHealthScore | null>(null)
  const growth      = ref<GrowthData | null>(null)

  async function fetchAll() { ... }

  return { overview, cmgr, forecast, anomalies, arHealth, growth, fetchAll }
})
```

### 9.4 useNotificationStore (notification.store.ts)

```typescript
export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<Notification[]>([])
  const unreadCount   = computed(() => notifications.value.filter(n => !n.read).length)

  function addNotification(n: Notification) { ... }
  function markRead(id: number) { ... }
  function markAllRead() { ... }

  return { notifications, unreadCount, addNotification, markRead, markAllRead }
})
```

### 9.5 useLoanStore (loan.store.ts)

```typescript
export const useLoanStore = defineStore('loan', () => {
  // Admin: all loans
  const allLoans     = ref<Loan[]>([])
  // Employee: own loans
  const myLoans      = ref<Loan[]>([])

  async function fetchAll() { ... }
  async function approve(id: number, data: LoanApprovalData) { ... }
  async function reject(id: number, note: string) { ... }
  async function apply(data: LoanApplicationData) { ... }

  return { allLoans, myLoans, fetchAll, approve, reject, apply }
})
```

### 9.6 useEmployeeStore (employee.store.ts)

```typescript
export const useEmployeeStore = defineStore('employee', () => {
  // Admin context
  const employees    = ref<Employee[]>([])
  const pagination   = ref<Pagination | null>(null)
  // Employee context (own profile)
  const myProfile    = ref<Employee | null>(null)

  async function fetchList(params?: ListParams) { ... }
  async function create(data: EmployeeCreateData) { ... }
  async function update(id: number, data: Partial<EmployeeCreateData>) { ... }
  async function resetPasskey(id: number) { ... }
  async function fetchMyProfile() { ... }

  return { employees, pagination, myProfile, fetchList, create, update, resetPasskey, fetchMyProfile }
})
```

---

## 10. Composables

### 10.1 usePayroll.ts

```typescript
// Bridges PayrollStore + PayrollService + reactive UI state
export function usePayroll() {
  const store   = usePayrollStore()
  const loading = ref(false)
  const error   = ref<string | null>(null)

  async function loadMonth(month: string) {
    loading.value = true
    await store.fetchMonthPayroll(month)
    loading.value = false
  }

  return { loading, error, salaryMonths: store.salaryMonths, loadMonth, ... }
}
```

### 10.2 useRealTime.ts (Pusher + Laravel Echo)

```typescript
// Manages all Pusher channel subscriptions
export function useRealTime() {
  const notifStore = useNotificationStore()
  const payStore   = usePayrollStore()

  function subscribeAdmin() {
    Echo.private('admin-broadcast')
      .listen('loan.applied',     (e) => notifStore.addNotification(...))
      .listen('leave.applied',    (e) => notifStore.addNotification(...))
      .listen('invoice.overdue',  (e) => notifStore.addNotification(...))
      .listen('message.new',      (e) => notifStore.addNotification(...))
  }

  function subscribeEmployee(employeeId: number) {
    Echo.private(`employee.${employeeId}`)
      .listen('salary.processed',       (e) => { payStore.refresh(); notifStore.add(...) })
      .listen('loan.approved',          (e) => notifStore.add(...))
      .listen('message.action_taken',   (e) => { /* trigger payslip refresh */ })
  }

  return { subscribeAdmin, subscribeEmployee }
}
```

---

## 11. API Layer (Services)

### 11.1 api.service.ts — Axios Instance

```typescript
import axios from 'axios'
import { useAuthStore } from '@/stores/auth.store'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
})

// Request interceptor — attach Sanctum token
api.interceptors.request.use(config => {
  const auth = useAuthStore()
  if (auth.token) config.headers.Authorization = `Bearer ${auth.token}`
  return config
})

// Response interceptor — handle 401
api.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      useAuthStore().logout()
    }
    return Promise.reject(err)
  }
)

export default api
```

### 11.2 payroll.service.ts

```typescript
import api from './api.service'

export const PayrollService = {
  getMonth: (month: string)          => api.get(`/admin/payroll/${month}`),
  process:  (data: ProcessPayload)   => api.post('/admin/payroll/process', data),
  bulkProcess: (month: string)       => api.post('/admin/payroll/bulk-process', { month }),
  override: (id: number, data: any)  => api.put(`/admin/payroll/${id}`, data),
  markPaid: (id: number)             => api.post(`/admin/payroll/${id}/mark-paid`),
  getPayslip: (month: string)        => api.get(`/employee/salary/${month}/payslip`),
  downloadPayslipPDF: (month: string) => api.get(`/employee/salary/${month}/payslip/pdf`, { responseType: 'blob' }),
}
```

---

## 12. Real-Time (Pusher + Laravel Echo)

### 12.1 Echo Setup (main.ts)

```typescript
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher
window.Echo = new Echo({
  broadcaster:  'pusher',
  key:          import.meta.env.VITE_PUSHER_KEY,
  cluster:      import.meta.env.VITE_PUSHER_CLUSTER,
  forceTLS:     true,
  authEndpoint: `${import.meta.env.VITE_API_URL}/broadcasting/auth`,
  auth: {
    headers: {
      Authorization: `Bearer ${localStorage.getItem('fin_token')}`
    }
  }
})
```

### 12.2 All Channel Subscriptions

| Channel | Events | Direction |
|---|---|---|
| `admin-broadcast` (private) | loan.applied, leave.applied, invoice.overdue, liability.due_soon, message.new | Admin receives |
| `employee.{id}` (private) | salary.processed, salary.paid, loan.approved, loan.rejected, leave.decision, message.replied, message.resolved, message.action_taken | Employee receives |

---

## 13. Router & Route Guards

### 13.1 Route Definitions

```typescript
const routes = [
  { path: '/login',  component: LoginView, meta: { layout: 'auth', public: true } },

  // Admin routes
  { path: '/admin',  component: AdminLayout, meta: { requiresAuth: true, role: 'admin' },
    children: [
      { path: 'dashboard',      component: () => import('@/views/admin/DashboardView.vue') },
      { path: 'employees',      component: () => import('@/views/admin/employees/EmployeeListView.vue') },
      { path: 'payroll',        component: () => import('@/views/admin/payroll/PayrollView.vue') },
      { path: 'analytics',      component: () => import('@/views/admin/analytics/AnalyticsOverviewView.vue') },
      { path: 'growth',         component: () => import('@/views/admin/analytics/GrowthDashboardView.vue') },
      { path: 'messages',       component: () => import('@/views/admin/messages/MessageCenterView.vue') },
      // ... etc
    ]
  },

  // Employee routes
  { path: '/portal', component: EmployeeLayout, meta: { requiresAuth: true, role: 'employee' },
    children: [
      { path: 'dashboard',   component: () => import('@/views/employee/DashboardView.vue') },
      { path: 'salary',      component: () => import('@/views/employee/salary/SalaryHistoryView.vue') },
      { path: 'loans',       component: () => import('@/views/employee/loans/LoanStatusView.vue') },
      { path: 'attendance',  component: () => import('@/views/employee/attendance/AttendanceView.vue') },
      { path: 'inbox',       component: () => import('@/views/employee/messages/InboxView.vue') },
    ]
  },
]
```

### 13.2 Guards (guards.ts)

```typescript
router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.public) return next()
  if (!auth.token)    return next('/login')

  // Role-based redirect
  if (to.meta.role === 'admin' && auth.role === 'employee') return next('/portal/dashboard')
  if (to.meta.role === 'employee' && auth.role !== 'employee') return next('/admin/dashboard')

  next()
})
```

---

## 14. All Views Reference

### Admin Views

| View File | Route | Purpose |
|---|---|---|
| `LoginView.vue` | `/login` | Email + passkey login form |
| `DashboardView.vue` | `/admin/dashboard` | KPI cards, revenue charts, pending items |
| `EmployeeListView.vue` | `/admin/employees` | Searchable, filterable employee table |
| `EmployeeCreateView.vue` | `/admin/employees/create` | Create form + passkey modal |
| `EmployeeDetailView.vue` | `/admin/employees/:id` | Full profile + salary history |
| `PayrollView.vue` | `/admin/payroll` | Month-based payroll processing table |
| `PayslipView.vue` | `/admin/payroll/:id/payslip` | Full payslip breakdown |
| `LoanManagementView.vue` | `/admin/loans` | All loans — approve / reject |
| `ProjectListView.vue` | `/admin/projects` | Client + project list |
| `InvoiceView.vue` | `/admin/projects/:id/invoices` | Invoice lifecycle management |
| `ExpenseView.vue` | `/admin/expenses` | Expense list + recurring setup |
| `LiabilityView.vue` | `/admin/liabilities` | Liability registry + amortization |
| `AssetView.vue` | `/admin/assets` | Asset registry + depreciation |
| `AnalyticsOverviewView.vue` | `/admin/analytics` | Full analytics dashboard |
| `GrowthDashboardView.vue` | `/admin/growth` | CMGR + growth velocity dashboard |
| `MessageCenterView.vue` | `/admin/messages` | Employee query management |
| `SettingsView.vue` | `/admin/settings` | System config, late policy, holidays |

### Employee Views

| View File | Route | Purpose |
|---|---|---|
| `DashboardView.vue` | `/portal/dashboard` | KPI cards, salary summary, attendance widget |
| `SalaryHistoryView.vue` | `/portal/salary` | Full salary history table with expand |
| `PayslipDetailView.vue` | `/portal/salary/:month` | Individual payslip detail |
| `LoanStatusView.vue` | `/portal/loans` | Own loan status + repayment progress |
| `LoanApplyView.vue` | `/portal/loans/apply` | Loan application form with EMI preview |
| `LeaveView.vue` | `/portal/leaves` | Leave history + apply for leave |
| `AttendanceView.vue` | `/portal/attendance` | HeatMap calendar + deduction summary |
| `InboxView.vue` | `/portal/inbox` | Message list + compose new query |

---

## 15. Development Roadmap

| Phase | Duration | Frontend Deliverables |
|---|---|---|
| Phase 1: Foundation | Weeks 1–3 | Vite + Vue 3 setup, TailwindCSS v4 config, Router, Pinia, Axios service, AuthLayout, AdminLayout, EmployeeLayout, Login page, Design tokens, Base UI components |
| Phase 2: Core HR & Payroll | Weeks 4–6 | Payroll views, PayslipCard, SalaryHistoryView, Employee portal salary & attendance pages, HeatMap.vue, BarChart.vue, DonutChart.vue |
| Phase 3: Financial Modules | Weeks 7–9 | Project/Invoice views, Expense views, Liability views, Asset views, FunnelChart.vue, ProgressBar.vue |
| Phase 4: Loan System | Weeks 10–11 | LoanManagementView, LoanStatusView, LoanApplyView, EMI preview calculator, Pusher loan events UI |
| Phase 5: Real-Time & Notifications | Week 12 | NotificationBell, useRealTime composable, all Pusher event handlers, MessageCenterView, InboxView, MessageComposeForm |
| Phase 6: Analytics & Reporting | Weeks 13–15 | AnalyticsOverviewView, GrowthDashboardView, LineChart, AreaChart, GaugeChart, SparkLine, GrowthVelocityCard, AnomalyAlert |
| Phase 7: Polish & Launch | Weeks 16–17 | PDF download flows, SkeletonLoader states, mobile responsive audit, performance (lazy routes, memoized computed), browser testing |

---

*— FinERP Frontend Guide v2.0 — Vue.js 3 · TailwindCSS v4 · Pinia · Pusher + Laravel Echo —*
