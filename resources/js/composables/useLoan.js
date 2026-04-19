import { useLoanStore } from '../stores/loan.store';

export function useLoan() {
    const store = useLoanStore();
    return {
        ...store,
    };
}
