import { useEmployeeStore } from '../stores/employee.store';

export function useEmployee() {
    const store = useEmployeeStore();
    return {
        ...store,
    };
}
