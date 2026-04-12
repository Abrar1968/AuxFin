import { ref } from 'vue';
import { defineStore } from 'pinia';
import { EmployeeService } from '../services/employee.service';

export const useEmployeeStore = defineStore('employee', () => {
    const employees = ref([]);
    const pagination = ref(null);

    async function fetchList(params = {}) {
        const response = await EmployeeService.list(params);
        employees.value = response.data.data ?? [];
        pagination.value = {
            total: response.data.total,
            per_page: response.data.per_page,
            current_page: response.data.current_page,
            last_page: response.data.last_page,
        };
    }

    return { employees, pagination, fetchList };
});
