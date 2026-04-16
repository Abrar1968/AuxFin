<template>
    <article class="fin-card h-full p-5 md:p-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.11em] text-slate-500">{{ title }}</p>
                <p v-if="subtitle" class="mt-1 text-xs text-slate-500">{{ subtitle }}</p>
            </div>
            <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full" :class="deltaDirection === 'up' ? 'bg-emerald-500' : deltaDirection === 'down' ? 'bg-rose-500' : 'bg-slate-400'"></span>
        </div>

        <p class="mt-3 text-2xl font-black text-slate-900 md:text-[1.75rem]">{{ value }}</p>

        <div class="mt-2 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold" :class="deltaClass">
            <span>{{ deltaIcon }}</span>
            <span>{{ delta }}</span>
        </div>

        <SparkLine v-if="sparklineData.length" class="mt-3" :data="sparklineData" :color="sparklineColor" />
    </article>
</template>

<script setup>
import { computed } from 'vue';
import SparkLine from '../charts/SparkLine.vue';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    value: { type: [String, Number], required: true },
    delta: { type: String, default: '0.00%' },
    deltaType: { type: String, default: 'up' },
    sparklineData: { type: Array, default: () => [] },
});

const deltaDirection = computed(() => {
    if (props.deltaType === 'up') return 'up';
    if (props.deltaType === 'down') return 'down';
    return 'flat';
});

const deltaClass = computed(() => {
    if (deltaDirection.value === 'up') return 'bg-emerald-50 text-emerald-700';
    if (deltaDirection.value === 'down') return 'bg-rose-50 text-rose-700';
    return 'bg-slate-100 text-slate-600';
});

const deltaIcon = computed(() => {
    if (deltaDirection.value === 'up') return '▲';
    if (deltaDirection.value === 'down') return '▼';
    return '•';
});

const sparklineColor = computed(() => {
    if (deltaDirection.value === 'up') return '#10b981';
    if (deltaDirection.value === 'down') return '#ef4444';
    return '#64748b';
});
</script>
