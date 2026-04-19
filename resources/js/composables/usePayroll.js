import { usePayrollStore } from '../stores/payroll.store';

export function usePayroll() {
    const store = usePayrollStore();
    return {
        salaryMonths: store.salaryMonths,
        loading: store.loading,
        fetchMonthPayroll: store.fetchMonthPayroll,
    };
}
