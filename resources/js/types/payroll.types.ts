export type PayrollStatus = 'draft' | 'processed' | 'paid';

export interface SalaryMonthRecord {
    id: number;
    employee_id: number;
    month: string;
    gross_earnings: number;
    total_deductions: number;
    net_payable: number;
    status: PayrollStatus;
}

export interface PayslipMeta {
    month: string;
    payment_date: string | null;
    status: PayrollStatus;
}

export interface PayslipEmployee {
    name: string;
    employee_code: string;
    department: string | null;
    designation: string | null;
}

export interface PayslipLoan {
    loan_reference: string | null;
    status: string | null;
    emi_amount: number;
    amount_remaining: number;
    months_left: number;
    repayment_schedule: Array<{
        month: string;
        amount_paid: number;
    }>;
}

export interface PayslipPayload {
    meta: PayslipMeta;
    employee: PayslipEmployee;
    earnings: Record<string, number>;
    deductions: Record<string, number>;
    net_payable: number;
    month_over_month_delta_percent: number;
    loan: PayslipLoan;
}
