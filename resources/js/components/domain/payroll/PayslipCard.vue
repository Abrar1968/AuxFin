<template>
    <article class="fin-card-panel p-5 space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-slate-900">{{ title }}</h3>
                <p class="text-sm text-slate-600">{{ subtitle }}</p>
            </div>
            <p class="text-right text-xs text-slate-500">
                Net Payable
                <span class="block text-lg font-extrabold text-indigo-700 mono">{{ money(netPayable) }}</span>
            </p>
        </header>

        <SalaryBreakdownTable :earnings="earnings" :deductions="deductions" />
    </article>
</template>

<script setup>
import SalaryBreakdownTable from './SalaryBreakdownTable.vue';

defineProps({
    title: { type: String, default: 'Payslip Summary' },
    subtitle: { type: String, default: '' },
    earnings: { type: Array, default: () => [] },
    deductions: { type: Array, default: () => [] },
    netPayable: { type: [Number, String], default: 0 },
});

function money(value) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(value ?? 0));
}
</script>
