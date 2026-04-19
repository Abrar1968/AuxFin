import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

function number(v) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(v ?? 0));
}

function titleBlock(doc, title, subtitle) {
    doc.setFontSize(16);
    doc.text(title, 40, 42);
    doc.setFontSize(10);
    doc.setTextColor(90, 90, 90);
    doc.text(subtitle, 40, 58);
    doc.setTextColor(0, 0, 0);
}

export function exportProfitLossPdf(payload, fileName = null) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fileName ?? `profit-loss-${payload.from}-to-${payload.to}.pdf`;

    titleBlock(doc, 'Profit & Loss Report', `Period: ${payload.from} to ${payload.to}`);

    autoTable(doc, {
        startY: 72,
        head: [['Month', 'Revenue', 'Payroll', 'OpEx', 'Liability', 'Gross', 'Net', 'Tax', 'After Tax']],
        body: (payload.rows ?? []).map((row) => [
            row.month,
            number(row.revenue),
            number(row.payroll),
            number(row.opex),
            number(row.liability_cost),
            number(row.gross_profit),
            number(row.net_profit),
            number(row.estimated_tax),
            number(row.profit_after_tax),
        ]),
        styles: { fontSize: 8, cellPadding: 4 },
        headStyles: { fillColor: [24, 39, 72] },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Total Revenue', 'Total Payroll', 'Total Net Profit', 'Total Tax', 'Total Profit After Tax']],
        body: [[
            number(payload.totals?.revenue),
            number(payload.totals?.payroll),
            number(payload.totals?.net_profit),
            number(payload.totals?.estimated_tax),
            number(payload.totals?.profit_after_tax),
        ]],
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [12, 94, 62] },
    });

    doc.save(filename);
}

export function exportTaxSummaryPdf(payload, fileName = null) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fileName ?? `tax-summary-${payload.from}-to-${payload.to}.pdf`;

    titleBlock(doc, 'Tax Summary Report', `Period: ${payload.from} to ${payload.to} | Corporate Tax Rate: ${payload.tax_rate_percent}%`);

    autoTable(doc, {
        startY: 72,
        head: [['Month', 'Taxable Profit', 'Corporate Tax Estimate', 'Payroll TDS Collected']],
        body: (payload.rows ?? []).map((row) => [
            row.month,
            number(row.taxable_profit),
            number(row.corporate_tax_estimate),
            number(row.payroll_tds_collected),
        ]),
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [24, 39, 72] },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Total Taxable Profit', 'Total Corporate Tax', 'Total Payroll TDS']],
        body: [[
            number(payload.totals?.taxable_profit),
            number(payload.totals?.corporate_tax_estimate),
            number(payload.totals?.payroll_tds_collected),
        ]],
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [12, 94, 62] },
    });

    doc.save(filename);
}

export function exportArAgingPdf(payload, fileName = null) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fileName ?? `ar-aging-${payload.as_of}.pdf`;

    titleBlock(doc, 'AR Aging Report', `As of: ${payload.as_of}`);

    autoTable(doc, {
        startY: 72,
        head: [['Bucket', 'Amount', 'Percent']],
        body: Object.entries(payload.distribution ?? {}).map(([bucket, row]) => [
            bucket,
            number(row.amount),
            `${number(row.percent)}%`,
        ]),
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [24, 39, 72] },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Invoice', 'Client', 'Due Date', 'Age (Days)', 'Bucket', 'Outstanding']],
        body: (payload.items ?? []).map((item) => [
            item.invoice_number,
            item.client_name ?? '-',
            item.due_date,
            String(item.age_days),
            item.bucket,
            number(item.outstanding),
        ]),
        styles: { fontSize: 8, cellPadding: 4 },
        headStyles: { fillColor: [12, 94, 62] },
    });

    doc.save(filename);
}

export function exportTrialBalancePdf(payload, fileName = null) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fileName ?? `trial-balance-${payload.as_of ?? 'as-of'}.pdf`;

    titleBlock(doc, 'Trial Balance', `As of: ${payload.as_of ?? '-'}`);

    autoTable(doc, {
        startY: 72,
        head: [['Account Code', 'Account Name', 'Debit', 'Credit']],
        body: (payload.lines ?? []).map((line) => [
            line.account_code,
            line.account_name,
            number(line.debit),
            number(line.credit),
        ]),
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [24, 39, 72] },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Total Debit', 'Total Credit', 'Difference', 'Balanced']],
        body: [[
            number(payload.totals?.debit),
            number(payload.totals?.credit),
            number(payload.totals?.difference),
            payload.is_balanced ? 'Yes' : 'No',
        ]],
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [12, 94, 62] },
    });

    doc.save(filename);
}

export function exportBalanceSheetPdf(payload, fileName = null) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fileName ?? `balance-sheet-${payload.as_of ?? 'as-of'}.pdf`;

    titleBlock(doc, 'Balance Sheet', `As of: ${payload.as_of ?? '-'}`);

    autoTable(doc, {
        startY: 72,
        head: [['Section', 'Item', 'Amount']],
        body: [
            ['Assets', 'Cash and Bank', number(payload.assets?.cash_and_bank)],
            ['Assets', 'Accounts Receivable', number(payload.assets?.accounts_receivable)],
            ['Assets', 'Prepaid Expenses', number(payload.assets?.prepaid_expenses)],
            ['Assets', 'Fixed Assets', number(payload.assets?.fixed_assets)],
            ['Liabilities', 'Outstanding Liabilities', number(payload.liabilities?.outstanding_liabilities)],
            ['Liabilities', 'Accounts Payable', number(payload.liabilities?.accounts_payable)],
            ['Liabilities', 'Bank Overdraft', number(payload.liabilities?.bank_overdraft)],
            ['Equity', 'Owner Capital', number(payload.equity?.owner_capital)],
            ['Equity', 'Owner Drawings', number(payload.equity?.owner_drawings)],
            ['Equity', 'Retained Earnings', number(payload.equity?.retained_earnings)],
        ],
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [24, 39, 72] },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Total Assets', 'Total Liabilities', 'Total Equity', 'L+E', 'Balanced']],
        body: [[
            number(payload.totals?.assets),
            number(payload.totals?.liabilities),
            number(payload.totals?.equity),
            number(payload.totals?.liabilities_plus_equity),
            payload.is_balanced ? 'Yes' : 'No',
        ]],
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [12, 94, 62] },
    });

    doc.save(filename);
}

export function exportCashFlowPdf(payload, fileName = null) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fileName ?? `cash-flow-${payload.from ?? 'from'}-to-${payload.to ?? 'to'}.pdf`;

    titleBlock(doc, 'Cash Flow Statement', `Period: ${payload.from ?? '-'} to ${payload.to ?? '-'}`);

    autoTable(doc, {
        startY: 72,
        head: [['Month', 'Opening', 'Collections', 'Operating Outflow', 'Financing Outflow', 'Net Cash Flow', 'Closing']],
        body: (payload.rows ?? []).map((row) => [
            row.month,
            number(row.opening_balance),
            number(row.cash_in_collections),
            number(row.operating_outflow),
            number(row.financing_outflow),
            number(row.net_cash_flow),
            number(row.closing_balance),
        ]),
        styles: { fontSize: 8, cellPadding: 4 },
        headStyles: { fillColor: [24, 39, 72] },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Collections', 'Operating Outflow', 'Financing Outflow', 'Net Cash Flow', 'Ending Balance']],
        body: [[
            number(payload.totals?.cash_in_collections),
            number(payload.totals?.operating_outflow),
            number(payload.totals?.financing_outflow),
            number(payload.totals?.net_cash_flow),
            number(payload.totals?.ending_balance),
        ]],
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [12, 94, 62] },
    });

    doc.save(filename);
}

export function exportGeneralLedgerPdf(payload, fileName = null) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fileName ?? `general-ledger-${payload.from ?? 'from'}-to-${payload.to ?? 'to'}.pdf`;

    titleBlock(doc, 'General Ledger', `Period: ${payload.from ?? '-'} to ${payload.to ?? '-'}`);

    autoTable(doc, {
        startY: 72,
        head: [['Date', 'Type', 'Reference', 'Description', 'Debit', 'Credit', 'Amount']],
        body: (payload.entries ?? []).map((entry) => [
            entry.entry_date,
            String(entry.entry_type ?? '').replaceAll('_', ' '),
            entry.reference,
            entry.description,
            entry.debit_account,
            entry.credit_account,
            number(entry.amount),
        ]),
        styles: { fontSize: 8, cellPadding: 4 },
        headStyles: { fillColor: [24, 39, 72] },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Total Debit', 'Total Credit']],
        body: [[
            number(payload.summary?.total_debit),
            number(payload.summary?.total_credit),
        ]],
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [12, 94, 62] },
    });

    doc.save(filename);
}

export function exportPaymentLedgerPdf(payload, fileName = null) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fileName ?? `payment-ledger-${payload.from ?? 'from'}-to-${payload.to ?? 'to'}.pdf`;

    titleBlock(doc, 'Project Payment Ledger', `Period: ${payload.from ?? '-'} to ${payload.to ?? '-'}`);

    autoTable(doc, {
        startY: 72,
        head: [['Date', 'Project', 'Invoice', 'Method', 'Reference', 'Amount']],
        body: (payload.entries ?? []).map((entry) => [
            entry.payment_date,
            entry.project_name ?? '-',
            entry.invoice_number ?? '-',
            entry.payment_method ?? '-',
            entry.reference_number ?? '-',
            number(entry.amount),
        ]),
        styles: { fontSize: 8, cellPadding: 4 },
        headStyles: { fillColor: [24, 39, 72] },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Total Amount', 'Payment Count']],
        body: [[
            number(payload.summary?.total_amount),
            String(payload.summary?.payment_count ?? 0),
        ]],
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [12, 94, 62] },
    });

    doc.save(filename);
}

export function exportInvoiceLedgerPdf(payload, fileName = null) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fileName ?? `invoice-ledger-project-${payload.project_id ?? 'x'}.pdf`;
    const rows = payload.rows ?? [];

    titleBlock(
        doc,
        'Project Invoice Ledger',
        `Project: ${payload.project_name ?? '-'} | Client: ${payload.client_name ?? '-'} | Count: ${rows.length}`,
    );

    autoTable(doc, {
        startY: 72,
        head: [['Invoice #', 'Amount', 'Invoice Date', 'Due Date', 'Partial', 'Status', 'Paid At']],
        body: rows.map((row) => [
            row.invoice_number ?? '-',
            number(row.amount),
            row.invoice_date ?? '-',
            row.due_date ?? '-',
            number(row.partial_amount),
            row.status ?? '-',
            row.payment_completed_at ?? '-',
        ]),
        styles: { fontSize: 8, cellPadding: 4 },
        headStyles: { fillColor: [24, 39, 72] },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Invoice Count', 'Total Amount', 'Total Collected', 'Total Outstanding']],
        body: [[
            String(rows.length),
            number(rows.reduce((sum, row) => sum + Number(row.amount ?? 0), 0)),
            number(rows.reduce((sum, row) => sum + Number(row.partial_amount ?? 0), 0)),
            number(rows.reduce((sum, row) => sum + Math.max(0, Number(row.amount ?? 0) - Number(row.partial_amount ?? 0)), 0)),
        ]],
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [12, 94, 62] },
    });

    doc.save(filename);
}

export function exportProjectPaymentRecordsPdf(payload, fileName = null) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fileName ?? `project-payments-${payload.project_id ?? 'x'}.pdf`;
    const rows = payload.rows ?? [];

    titleBlock(
        doc,
        'Project Payment Records',
        `Project: ${payload.project_name ?? '-'} | Client: ${payload.client_name ?? '-'} | Count: ${rows.length}`,
    );

    autoTable(doc, {
        startY: 72,
        head: [['Date', 'Invoice #', 'Method', 'Reference', 'Amount']],
        body: rows.map((row) => [
            row.payment_date ?? '-',
            row.invoice_number ?? '-',
            row.payment_method ?? '-',
            row.reference_number ?? '-',
            number(row.amount),
        ]),
        styles: { fontSize: 8, cellPadding: 4 },
        headStyles: { fillColor: [24, 39, 72] },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Payment Count', 'Total Collected']],
        body: [[
            String(rows.length),
            number(rows.reduce((sum, row) => sum + Number(row.amount ?? 0), 0)),
        ]],
        styles: { fontSize: 9, cellPadding: 5 },
        headStyles: { fillColor: [12, 94, 62] },
    });

    doc.save(filename);
}

function normalizeList(values) {
    if (!Array.isArray(values) || values.length === 0) {
        return '-';
    }

    return values.map((value) => `- ${String(value ?? '').trim()}`).join('\n');
}

function exportManualPdf(payload, fallbackTitle, fallbackFileName) {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const filename = fallbackFileName;
    const title = payload?.title ?? fallbackTitle;
    const generatedAt = payload?.generated_at
        ? new Date(payload.generated_at).toLocaleString()
        : new Date().toLocaleString();

    titleBlock(doc, title, `Generated: ${generatedAt}`);

    autoTable(doc, {
        startY: 72,
        head: [['Section', 'Route', 'Purpose', 'Quick Workflow', 'Demo Input', 'Expected Output']],
        body: (payload?.sections ?? []).map((section) => [
            section.name ?? '-',
            section.route ?? '-',
            section.purpose ?? '-',
            normalizeList(section.workflow),
            normalizeList(section.demo_input),
            normalizeList(section.expected_output),
        ]),
        styles: { fontSize: 7, cellPadding: 4, valign: 'top' },
        headStyles: { fillColor: [24, 39, 72] },
        columnStyles: {
            0: { cellWidth: 72 },
            1: { cellWidth: 70 },
            2: { cellWidth: 108 },
            3: { cellWidth: 110 },
            4: { cellWidth: 100 },
            5: { cellWidth: 100 },
        },
    });

    autoTable(doc, {
        startY: doc.lastAutoTable.finalY + 12,
        head: [['Equation', 'Formula', 'Demo Input', 'Demo Output']],
        body: (payload?.equations ?? []).map((equation) => [
            equation.name ?? '-',
            equation.formula ?? '-',
            equation.demo_input ?? '-',
            equation.demo_output ?? '-',
        ]),
        styles: { fontSize: 8, cellPadding: 5, valign: 'top' },
        headStyles: { fillColor: [12, 94, 62] },
        columnStyles: {
            0: { cellWidth: 120 },
            1: { cellWidth: 150 },
            2: { cellWidth: 120 },
            3: { cellWidth: 120 },
        },
    });

    const analyticsMetrics = payload?.analytics_metrics ?? [];
    if (analyticsMetrics.length > 0) {
        autoTable(doc, {
            startY: doc.lastAutoTable.finalY + 12,
            head: [['Module', 'Metric', 'Route', 'Chart', 'Formula', 'How To Use', 'Decision Guide']],
            body: analyticsMetrics.map((metric) => [
                metric.module ?? '-',
                metric.metric ?? '-',
                metric.route ?? '-',
                metric.chart ?? '-',
                metric.formula ?? '-',
                normalizeList(metric.how_to_use),
                normalizeList(metric.decision_guide),
            ]),
            styles: { fontSize: 7, cellPadding: 4, valign: 'top' },
            headStyles: { fillColor: [41, 80, 140] },
            columnStyles: {
                0: { cellWidth: 58 },
                1: { cellWidth: 66 },
                2: { cellWidth: 52 },
                3: { cellWidth: 55 },
                4: { cellWidth: 85 },
                5: { cellWidth: 86 },
                6: { cellWidth: 86 },
            },
        });
    }

    doc.save(filename);
}

export function exportAdminManualPdf(payload, fileName = null) {
    const filename = fileName ?? 'admin-docs-manual.pdf';
    exportManualPdf(payload, 'Superadmin/Admin Operations Manual', filename);
}

export function exportEmployeeManualPdf(payload, fileName = null) {
    const filename = fileName ?? 'employee-docs-manual.pdf';
    exportManualPdf(payload, 'Employee Self-Service Manual', filename);
}
