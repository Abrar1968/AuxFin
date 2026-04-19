export const employeeManualTitle = 'Employee Self-Service Manual';

export const employeeManualSections = [
    {
        name: 'Dashboard',
        route: '/portal/dashboard',
        purpose: 'Quick personal overview of salary, attendance, leave, and alerts for the current period.',
        workflow: [
            'Open dashboard after login.',
            'Review summary cards and latest update widgets.',
            'Use linked cards to open related detail pages.',
        ],
        demo_input: [
            'Seeded employee account with salary and leave data',
            'Current month attendance records',
            'At least one message in inbox',
        ],
        expected_output: [
            'Cards show salary, leave, and attendance snapshot',
            'Recent activity widgets are populated',
            'Quick links navigate correctly to target modules',
        ],
    },
    {
        name: 'My Salary',
        route: '/portal/salary',
        purpose: 'View salary history, monthly payslip components, and net payable amounts.',
        workflow: [
            'Select target month from salary history list.',
            'Open payslip detail for component-level breakdown.',
            'Compare gross, deductions, and net fields.',
        ],
        demo_input: [
            'Basic 40000, Allowance 6000, Overtime 2400',
            'Tax 3000, EMI 5000, Other Deduction 1200',
            'Month 2026-03',
        ],
        expected_output: [
            'Gross salary = 48400.00',
            'Net salary = 39200.00',
            'Payslip lines match history table values',
        ],
    },
    {
        name: 'Loans',
        route: '/portal/loans',
        purpose: 'Apply for loans and monitor approved loan repayment progress and remaining balance.',
        workflow: [
            'Open loans and click apply loan for new request.',
            'Submit amount, tenure, and purpose details.',
            'Track status and repayment progress after approval.',
        ],
        demo_input: [
            'Loan amount 120000',
            'Tenure 24 months',
            'Paid installments 6',
        ],
        expected_output: [
            'Application status moves from pending to approved/rejected',
            'Repayment progress = 25 percent after 6 of 24 installments',
            'Remaining tenure and outstanding value are visible',
        ],
    },
    {
        name: 'Leaves',
        route: '/portal/leaves',
        purpose: 'Submit leave requests and track approval status with real-time leave balance updates.',
        workflow: [
            'Create leave request with type and date range.',
            'Submit request and monitor pending state.',
            'After approval, verify balance reduction and status update.',
        ],
        demo_input: [
            'Entitlement 20 days',
            'Requested leave 3 days',
            'Leave type: casual',
        ],
        expected_output: [
            'Request appears in leave history list',
            'Approved request changes status to approved',
            'Leave balance becomes 17 days',
        ],
    },
    {
        name: 'Attendance',
        route: '/portal/attendance',
        purpose: 'Check daily attendance records, present ratio, and late entry impacts.',
        workflow: [
            'Open attendance page and pick target month.',
            'Review day-wise status and late marks.',
            'Confirm monthly attendance percentage and summary counters.',
        ],
        demo_input: [
            'Working days 22',
            'Present days 20',
            'Late minutes total 30',
        ],
        expected_output: [
            'Attendance percentage = 90.91 percent',
            'Late or absent counts appear in summary area',
            'Monthly report aligns with payroll attendance effect',
        ],
    },
    {
        name: 'Inbox',
        route: '/portal/inbox',
        purpose: 'Receive and respond to admin messages related to payroll, policy, or actions.',
        workflow: [
            'Open inbox and sort unread messages first.',
            'Open message and review subject plus details.',
            'Reply to admin where clarification is required.',
        ],
        demo_input: [
            'Unread payroll clarification message',
            'Reply text with acknowledgment',
            'Priority set by sender',
        ],
        expected_output: [
            'Unread badge decreases after opening message',
            'Reply is visible in thread timeline',
            'Conversation status remains synchronized with admin center',
        ],
    },
    {
        name: 'Docs Manual',
        route: '/portal/docs',
        purpose: 'Employee-facing documentation page with full usage instructions and downloadable manual PDF.',
        workflow: [
            'Open Docs Manual from portal sidebar.',
            'Read workflow and equation examples for each portal section.',
            'Click Download Manual PDF for offline use.',
        ],
        demo_input: [
            'Logged-in employee account',
            'Browser download access',
            'Existing seeded employee records',
        ],
        expected_output: [
            'Manual page loads with section-by-section guidance',
            'Equation examples show input and output clearly',
            'PDF is downloaded as employee-docs-manual.pdf',
        ],
    },
];

export const employeeManualEquations = [
    {
        name: 'Gross Salary',
        formula: 'Gross Salary = Basic + Allowances + Overtime',
        demo_input: 'Basic 40000, Allowances 6000, Overtime 2400',
        demo_output: 'Gross Salary = 48400.00',
    },
    {
        name: 'Net Salary',
        formula: 'Net Salary = Gross Salary - Tax - Loan EMI - Other Deductions',
        demo_input: 'Gross 48400, Tax 3000, EMI 5000, Other 1200',
        demo_output: 'Net Salary = 39200.00',
    },
    {
        name: 'Loan Progress',
        formula: 'Progress percent = (Paid Installments / Total Installments) x 100',
        demo_input: 'Paid 6, Total 24',
        demo_output: 'Progress = 25.00 percent',
    },
    {
        name: 'Outstanding Loan',
        formula: 'Outstanding = Loan Principal - Total Repaid',
        demo_input: 'Principal 120000, Repaid 37200',
        demo_output: 'Outstanding = 82800.00',
    },
    {
        name: 'Leave Balance',
        formula: 'Leave Balance = Entitlement - Approved Leave',
        demo_input: 'Entitlement 20, Approved Leave 3',
        demo_output: 'Leave Balance = 17 days',
    },
    {
        name: 'Leave Utilization',
        formula: 'Utilization percent = (Approved Leave / Entitlement) x 100',
        demo_input: 'Approved 3, Entitlement 20',
        demo_output: 'Utilization = 15.00 percent',
    },
    {
        name: 'Attendance Percentage',
        formula: 'Attendance percent = (Present Days / Working Days) x 100',
        demo_input: 'Present 20, Working Days 22',
        demo_output: 'Attendance = 90.91 percent',
    },
    {
        name: 'Late Penalty Example',
        formula: 'Late Penalty = Late Minutes x Penalty Per Minute',
        demo_input: 'Late Minutes 30, Penalty Rate 10',
        demo_output: 'Late Penalty = 300.00',
    },
];
