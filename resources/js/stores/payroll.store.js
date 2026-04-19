import { ref } from 'vue';
import { defineStore } from 'pinia';
import { PayrollService } from '../services/payroll.service';

export const usePayrollStore = defineStore('payroll', () => {
    const salaryMonths = ref([]);
    const loading = ref(false);

    async function fetchMonthPayroll(month) {
        loading.value = true;
        try {
            const response = await PayrollService.getMonth(month);
            salaryMonths.value = response.data;
        } finally {
            loading.value = false;
        }
    }

    return { salaryMonths, loading, fetchMonthPayroll };
});
