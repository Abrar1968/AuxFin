export interface EmployeeDepartment {
    id: number;
    name: string;
}

export interface EmployeeUser {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
}

export interface EmployeeCompensation {
    basic_salary: number;
    house_rent: number;
    conveyance: number;
    medical_allowance: number;
    pf_rate: number;
    tds_rate: number;
    professional_tax: number;
}

export interface EmployeeSummary extends EmployeeCompensation {
    id: number;
    employee_code: string;
    designation: string;
    date_of_joining: string;
    bank_name: string | null;
    bank_account_number: string | null;
    working_days_per_week: number;
    weekly_off_days: string[];
    user: EmployeeUser;
    department: EmployeeDepartment | null;
}

export interface EmployeeCreatePayload {
    name: string;
    email: string;
    role?: 'employee' | 'admin';
    designation: string;
    date_of_joining: string;
    department_id?: number | null;
    basic_salary: number;
    house_rent?: number | null;
    conveyance?: number | null;
    medical_allowance?: number | null;
    pf_rate?: number | null;
    tds_rate?: number | null;
    professional_tax?: number | null;
}
