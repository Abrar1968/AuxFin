import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

function money(value) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function lineItemRows(data = {}) {
    return Object.entries(data)
        .filter(([key]) => !['gross_earnings', 'total_deductions'].includes(key))
        .map(([key, value]) => [
            key.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()),
            money(value),
        ]);
}

export function downloadPayslipPdf(payload, filename = 'payslip.pdf') {
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });

    const meta = payload?.meta ?? {};
    const employee = payload?.employee ?? {};
    const earnings = payload?.earnings ?? {};
    const deductions = payload?.deductions ?? {};

    doc.setFontSize(20);
    doc.setFont('helvetica', 'bold');
    doc.text('FinERP Payslip', 40, 50);

    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text(`Month: ${meta.month ?? '-'}`, 40, 70);
    doc.text(`Status: ${meta.status ?? '-'}`, 40, 84);
    doc.text(`Payment Date: ${meta.payment_date ?? 'Pending'}`, 40, 98);

    doc.text(`Employee: ${employee.name ?? '-'}`, 320, 70);
    doc.text(`Code: ${employee.employee_code ?? '-'}`, 320, 84);
    doc.text(`Department: ${employee.department ?? '-'}`, 320, 98);
    doc.text(`Designation: ${employee.designation ?? '-'}`, 320, 112);

    autoTable(doc, {
        startY: 132,
        head: [['Earnings', 'Amount']],
        body: [
            ...lineItemRows(earnings),
            ['Gross Earnings', money(earnings.gross_earnings)],
        ],
        theme: 'striped',
        headStyles: { fillColor: [15, 23, 42] },
        styles: { fontSize: 10 },
        margin: { left: 40, right: 320 },
    });

    autoTable(doc, {
        startY: 132,
        head: [['Deductions', 'Amount']],
        body: [
            ...lineItemRows(deductions),
            ['Total Deductions', money(deductions.total_deductions)],
        ],
        theme: 'striped',
        headStyles: { fillColor: [185, 28, 28] },
        styles: { fontSize: 10 },
        margin: { left: 320, right: 40 },
    });

    const nextY = Math.max(doc.lastAutoTable.finalY ?? 0, 380) + 24;

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.text(`Net Payable: ${money(payload?.net_payable)}`, 40, nextY);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    doc.text(`MoM Delta: ${payload?.month_over_month_delta_percent ?? 0}%`, 40, nextY + 18);

    const loan = payload?.loan ?? {};
    if (loan.loan_reference) {
        autoTable(doc, {
            startY: nextY + 38,
            head: [['Loan Reference', 'Status', 'EMI', 'Remaining', 'Months Left']],
            body: [[
                loan.loan_reference,
                loan.status ?? '-',
                money(loan.emi_amount),
                money(loan.amount_remaining),
                String(loan.months_left ?? 0),
            ]],
            theme: 'grid',
            styles: { fontSize: 9 },
            headStyles: { fillColor: [17, 94, 89] },
            margin: { left: 40, right: 40 },
        });
    }

    doc.setFontSize(8);
    doc.text(`Generated at ${new Date().toLocaleString()}`, 40, 812);

    doc.save(filename);
}
