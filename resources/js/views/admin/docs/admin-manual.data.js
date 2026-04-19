export const adminManualTitle = 'Superadmin/Admin Operations Manual';

export const adminManualSections = [
    {
        name: 'Dashboard',
        route: '/admin/dashboard',
        purpose: 'Central KPI summary for revenue, payroll pressure, receivables, and operational alerts.',
        workflow: [
            'Open dashboard and verify the selected period context.',
            'Review KPI cards, cash trend, and pending alerts blocks.',
            'Click the highlighted card to jump into the related module.',
        ],
        demo_input: [
            'Period: Current month',
            'Seeded invoices and payroll records available',
            'At least one overdue invoice in system',
        ],
        expected_output: [
            'Revenue and net metrics populate in top cards',
            'Overdue or pending indicators appear in alert area',
            'Card clicks route to matching detail pages',
        ],
    },
    {
        name: 'Employees',
        route: '/admin/employees',
        purpose: 'Create and manage employee profiles, salary setup, and status updates.',
        workflow: [
            'Click Add Employee and fill identity plus job details.',
            'Set salary components and deductions in profile form.',
            'Save record and confirm the employee row appears in table.',
        ],
        demo_input: [
            'Name: Amina Noor',
            'Department: Finance',
            'Basic Salary: 40000, Allowance: 6000',
        ],
        expected_output: [
            'New employee is visible in employee list',
            'Profile page opens with saved data',
            'Salary setup becomes available to payroll module',
        ],
    },
    {
        name: 'Payroll',
        route: '/admin/payroll',
        purpose: 'Run payroll cycles, verify payslips, and export salary records.',
        workflow: [
            'Select payroll month and employee scope.',
            'Generate payroll and inspect gross, deductions, net salary.',
            'Open payslip detail and export PDF as needed.',
        ],
        demo_input: [
            'Basic: 40000, Allowance: 6000, Overtime: 2400',
            'Tax: 3000, Loan EMI: 5000, Other Deduction: 1200',
            'Payroll Month: 2026-03',
        ],
        expected_output: [
            'Gross pay = 48400.00',
            'Net pay = 39200.00',
            'Payslip detail matches payroll table values',
        ],
    },
    {
        name: 'Loans',
        route: '/admin/loans',
        purpose: 'Approve, reject, and track employee loan applications and repayment schedules.',
        workflow: [
            'Open pending applications and review requested amount and reason.',
            'Approve with tenure and interest settings.',
            'Monitor repayment status from active loans table.',
        ],
        demo_input: [
            'Principal: 120000',
            'Tenure: 24 months',
            'Annual interest: 12 percent',
        ],
        expected_output: [
            'Monthly principal = 5000.00',
            'Monthly interest = 1200.00',
            'Monthly EMI = 6200.00 with active status',
        ],
    },
    {
        name: 'Projects',
        route: '/admin/projects',
        purpose: 'Manage client projects, invoices, and payment collection progress.',
        workflow: [
            'Create project and assign client account details.',
            'Generate invoice and post payment transactions.',
            'Open invoice listing to verify remaining outstanding amount.',
        ],
        demo_input: [
            'Invoice total: 300000',
            'Collected: 180000',
            'Due date: 2026-04-15',
        ],
        expected_output: [
            'Outstanding amount = 120000.00',
            'Invoice bucket updates based on due date age',
            'Project ledger reflects collection event',
        ],
    },
    {
        name: 'Expenses',
        route: '/admin/expenses',
        purpose: 'Track operating expenses by category and month for profitability control.',
        workflow: [
            'Record expense with category and payment method.',
            'Filter by month to review category totals.',
            'Use export options for management reporting.',
        ],
        demo_input: [
            'Category: Utilities',
            'Amount: 28000',
            'Entry date: 2026-03-10',
        ],
        expected_output: [
            'Expense row appears with selected category',
            'Monthly total increases by 28000.00',
            'P and L operating expense updates on refresh',
        ],
    },
    {
        name: 'Liabilities',
        route: '/admin/liabilities',
        purpose: 'Track payable obligations and financing liabilities with due schedules.',
        workflow: [
            'Create liability item with principal and due plan.',
            'Record partial payment transactions.',
            'Review remaining balance and due exposure widgets.',
        ],
        demo_input: [
            'Principal: 500000',
            'Paid to date: 125000',
            'Type: Vendor payable',
        ],
        expected_output: [
            'Remaining liability = 375000.00',
            'Payable aging card reflects new due position',
            'Accounting reports receive liability movement',
        ],
    },
    {
        name: 'Assets',
        route: '/admin/assets',
        purpose: 'Register fixed assets and monitor depreciation and book value.',
        workflow: [
            'Add asset with purchase cost and service start date.',
            'Apply depreciation method and useful life.',
            'Review net book value in asset register.',
        ],
        demo_input: [
            'Asset cost: 240000',
            'Accumulated depreciation: 60000',
            'Asset class: IT Equipment',
        ],
        expected_output: [
            'Book value = 180000.00',
            'Asset row displays cost and depreciation fields',
            'Balance sheet fixed assets section updates',
        ],
    },
    {
        name: 'Leaves',
        route: '/admin/leaves',
        purpose: 'Approve leave requests and monitor leave balances by employee.',
        workflow: [
            'Open pending leave requests queue.',
            'Approve or reject with manager note.',
            'Validate balance deduction on employee leave card.',
        ],
        demo_input: [
            'Annual entitlement: 20 days',
            'Approved leave request: 3 days',
            'Employee: E-1009',
        ],
        expected_output: [
            'Remaining leave balance = 17 days',
            'Request status changes to approved',
            'Employee portal leave summary synchronizes',
        ],
    },
    {
        name: 'Attendance',
        route: '/admin/attendance',
        purpose: 'Review check-in records, late flags, and attendance compliance by period.',
        workflow: [
            'Filter attendance by month and department.',
            'Check late and absence counts per employee.',
            'Export attendance summary if payroll audit is required.',
        ],
        demo_input: [
            'Working days: 22',
            'Present days: 20',
            'Late arrivals: 2',
        ],
        expected_output: [
            'Attendance percentage = 90.91 percent',
            'Late count appears in employee attendance row',
            'Payroll attendance-dependent deductions can be verified',
        ],
    },
    {
        name: 'Analytics',
        route: '/admin/analytics',
        purpose: 'Visual insight board for trend analysis across payroll, expenses, and collections.',
        workflow: [
            'Open analytics and choose target month range.',
            'Review trend charts and top variance cards.',
            'Use insights to identify cost spikes and cash risks.',
        ],
        demo_input: [
            'Previous month expense: 420000',
            'Current month expense: 465000',
            'Collection target: 750000',
        ],
        expected_output: [
            'Expense growth displays +10.71 percent',
            'Collection variance card indicates target gap',
            'Charts render with updated period filters',
        ],
    },
    {
        name: 'Growth',
        route: '/admin/growth',
        purpose: 'Track month-on-month growth metrics for revenue, margin, and strategic KPIs.',
        workflow: [
            'Open growth panel for selected period comparison.',
            'Inspect metric-by-metric growth percentages.',
            'Capture opportunities and risk zones from highlighted deltas.',
        ],
        demo_input: [
            'Previous revenue: 1200000',
            'Current revenue: 1410000',
            'Previous margin: 18 percent',
        ],
        expected_output: [
            'Revenue growth = 17.50 percent',
            'Positive metrics display success styling',
            'Negative drifts display warning styling',
        ],
    },
    {
        name: 'Reports',
        route: '/admin/reports',
        purpose: 'Generate Profit and Loss, Tax Summary, and AR Aging executive reports.',
        workflow: [
            'Set From Month, To Month, and AR As Of date.',
            'Click Refresh Reports and wait for charts/tables.',
            'Export P and L, Tax, and AR Aging PDF outputs.',
        ],
        demo_input: [
            'Revenue: 1500000, Payroll: 520000, OpEx: 350000, Liability Cost: 65000',
            'Tax rate: 30 percent',
            'Receivable buckets seeded with 0_30d to 90plus balances',
        ],
        expected_output: [
            'Net profit and profit after tax cards populate',
            'Tax chart shows monthly taxable trend',
            'AR bucket percentages and invoice table load correctly',
        ],
    },
    {
        name: 'Accounting',
        route: '/admin/accounting',
        purpose: 'Review trial balance, balance sheet, cash flow, and ledger level accounting records.',
        workflow: [
            'Load accounting dashboard for target reporting range.',
            'Verify trial balance debit and credit equality.',
            'Export accounting statements as PDF for audit.',
        ],
        demo_input: [
            'Total debit: 980000',
            'Total credit: 980000',
            'Opening cash: 150000',
        ],
        expected_output: [
            'Trial balance is marked as balanced',
            'Balance sheet totals remain consistent',
            'Cash flow closing value reconciles from period totals',
        ],
    },
    {
        name: 'Messages',
        route: '/admin/messages',
        purpose: 'Internal communication panel for sending and tracking staff messages.',
        workflow: [
            'Compose message with recipient and subject.',
            'Send and verify unread badge in receiver inbox.',
            'Track reply thread in conversation panel.',
        ],
        demo_input: [
            'Recipient: employee id E-1012',
            'Subject: Payroll clarification',
            'Priority: normal',
        ],
        expected_output: [
            'Message appears in sent list instantly',
            'Unread badge count increases for recipient',
            'Thread view stores conversation history',
        ],
    },
    {
        name: 'Settings',
        route: '/admin/settings',
        purpose: 'Configure payroll defaults, tax policy, and system finance preferences.',
        workflow: [
            'Open settings and update payroll or tax parameters.',
            'Save configuration and confirm success notification.',
            'Re-run payroll/report to verify updated policy impact.',
        ],
        demo_input: [
            'Tax rate: 30 percent',
            'Overtime multiplier: 1.5',
            'Default workdays: 22',
        ],
        expected_output: [
            'Saved values persist after refresh',
            'Payroll and report calculations follow latest settings',
            'Configuration errors are blocked with validation message',
        ],
    },
    {
        name: 'Docs Manual',
        route: '/admin/docs',
        purpose: 'In-app knowledge base for onboarding, SOP, and downloadable admin manual PDF.',
        workflow: [
            'Open docs from sidebar in Communication section.',
            'Read workflow and equation examples per module.',
            'Click Download Manual PDF for offline reference.',
        ],
        demo_input: [
            'Any active admin account',
            'Browser with download permission',
            'Current seeded workspace data',
        ],
        expected_output: [
            'Manual displays all admin modules with examples',
            'Equation table provides demo calculations',
            'PDF is downloaded as admin-docs-manual.pdf',
        ],
    },
];

export const adminManualEquations = [
    {
        name: 'Gross Pay',
        formula: 'Gross Pay = Basic + Allowances + Overtime',
        demo_input: 'Basic 40000, Allowances 6000, Overtime 2400',
        demo_output: 'Gross Pay = 48400.00',
    },
    {
        name: 'Net Pay',
        formula: 'Net Pay = Gross Pay - Tax - Loan EMI - Other Deductions',
        demo_input: 'Gross 48400, Tax 3000, EMI 5000, Other 1200',
        demo_output: 'Net Pay = 39200.00',
    },
    {
        name: 'Overtime Value',
        formula: 'Overtime = Hourly Rate x Overtime Hours x Multiplier',
        demo_input: 'Hourly 250, Hours 8, Multiplier 1.5',
        demo_output: 'Overtime = 3000.00',
    },
    {
        name: 'Loan EMI',
        formula: 'Monthly EMI = (Principal / Tenure) + (Principal x Annual Rate / 12)',
        demo_input: 'Principal 120000, Tenure 24, Rate 0.12',
        demo_output: 'EMI = 6200.00',
    },
    {
        name: 'AR Outstanding',
        formula: 'Outstanding = Invoice Total - Collected Amount',
        demo_input: 'Invoice 300000, Collected 180000',
        demo_output: 'Outstanding = 120000.00',
    },
    {
        name: 'Net Profit',
        formula: 'Net Profit = Revenue - Payroll - Operating Expense - Liability Cost',
        demo_input: 'Revenue 1500000, Payroll 520000, OpEx 350000, Liability 65000',
        demo_output: 'Net Profit = 565000.00',
    },
    {
        name: 'Estimated Tax',
        formula: 'Estimated Tax = max(Net Profit, 0) x Tax Rate',
        demo_input: 'Net Profit 565000, Tax Rate 30 percent',
        demo_output: 'Estimated Tax = 169500.00',
    },
    {
        name: 'Profit After Tax',
        formula: 'Profit After Tax = Net Profit - Estimated Tax',
        demo_input: 'Net Profit 565000, Estimated Tax 169500',
        demo_output: 'Profit After Tax = 395500.00',
    },
    {
        name: 'Attendance Percentage',
        formula: 'Attendance percent = (Present Days / Working Days) x 100',
        demo_input: 'Present 20, Working Days 22',
        demo_output: 'Attendance = 90.91 percent',
    },
    {
        name: 'Leave Balance',
        formula: 'Leave Balance = Entitlement - Approved Leave Days',
        demo_input: 'Entitlement 20, Approved 3',
        demo_output: 'Leave Balance = 17 days',
    },
    {
        name: 'Asset Book Value',
        formula: 'Book Value = Purchase Cost - Accumulated Depreciation',
        demo_input: 'Cost 240000, Accumulated Depreciation 60000',
        demo_output: 'Book Value = 180000.00',
    },
    {
        name: 'Growth Rate',
        formula: 'Growth percent = ((Current - Previous) / Previous) x 100',
        demo_input: 'Current 1410000, Previous 1200000',
        demo_output: 'Growth = 17.50 percent',
    },
    {
        name: 'Cash Closing Balance',
        formula: 'Closing Cash = Opening + Collections - Operating Outflow - Financing Outflow',
        demo_input: 'Opening 150000, Collections 700000, Operating 420000, Financing 50000',
        demo_output: 'Closing Cash = 380000.00',
    },
];
