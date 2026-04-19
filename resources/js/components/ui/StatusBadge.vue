<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide shadow-[inset_0_0_0_1px_rgba(255,255,255,.65)]" :class="classes">
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: 'unknown' },
    label: { type: String, default: '' },
});

const normalized = computed(() => String(props.status ?? '').toLowerCase());
const label = computed(() => props.label || normalized.value.replaceAll('_', ' '));

const classes = computed(() => {
    if (['paid', 'active', 'approved', 'resolved', 'success', 'completed'].includes(normalized.value)) {
        return 'bg-emerald-100 text-emerald-800';
    }

    if (['pending', 'draft', 'under_review', 'processing'].includes(normalized.value)) {
        return 'bg-amber-100 text-amber-800';
    }

    if (['rejected', 'overdue', 'failed', 'defaulted'].includes(normalized.value)) {
        return 'bg-rose-100 text-rose-800';
    }

    return 'bg-slate-100 text-slate-800';
});
</script>
