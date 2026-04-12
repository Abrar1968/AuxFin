<template>
    <article class="fin-card p-4">
        <div class="flex items-center justify-between gap-2">
            <h4 class="text-sm font-bold text-slate-800">{{ title }}</h4>
            <StatusBadge :status="status" />
        </div>
        <p class="mt-2 text-xs text-slate-500">Outstanding</p>
        <p class="text-2xl font-extrabold text-slate-900 mono">{{ money(outstanding) }}</p>

        <div class="mt-3 h-2 rounded-full bg-slate-200 overflow-hidden">
            <div class="h-full rounded-full bg-(image:--color-gradient)" :style="{ width: `${progress}%` }"></div>
        </div>
        <p class="mt-1 text-xs text-slate-500">Repaid {{ progress.toFixed(0) }}%</p>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from '../../ui/StatusBadge.vue';

const props = defineProps({
    title: { type: String, default: 'Loan Status' },
    status: { type: String, default: 'pending' },
    approvedAmount: { type: [Number, String], default: 0 },
    outstanding: { type: [Number, String], default: 0 },
});

const progress = computed(() => {
    const total = Number(props.approvedAmount ?? 0);
    const left = Number(props.outstanding ?? 0);
    if (total <= 0) return 0;
    return Math.max(0, Math.min(100, ((total - left) / total) * 100));
});

function money(value) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(value ?? 0));
}
</script>
