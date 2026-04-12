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
