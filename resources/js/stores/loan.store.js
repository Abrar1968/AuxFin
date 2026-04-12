import { ref } from 'vue';
import { defineStore } from 'pinia';
import { LoanService } from '../services/loan.service';

export const useLoanStore = defineStore('loan', () => {
    const allLoans = ref([]);
    const myLoans = ref([]);

    async function fetchAll() {
        const response = await LoanService.adminList();
        allLoans.value = response.data.data ?? response.data;
    }

    async function fetchMine() {
        const response = await LoanService.myList();
        myLoans.value = response.data;
    }

    return { allLoans, myLoans, fetchAll, fetchMine };
});
