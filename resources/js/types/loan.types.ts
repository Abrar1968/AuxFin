export type LoanStatus = 'pending' | 'approved' | 'active' | 'completed' | 'rejected';

export interface LoanRecord {
    id: number;
    employee_id: number;
    loan_reference: string;
    amount_requested: number;
    amount_approved: number;
    repayment_months: number;
    emi_amount: number;
    start_month: string;
    amount_remaining: number;
    reason: string;
    status: LoanStatus;
    reviewed_at: string | null;
}

export interface LoanRepaymentRecord {
    id: number;
    loan_id: number;
    month: string;
    amount_paid: number;
}
