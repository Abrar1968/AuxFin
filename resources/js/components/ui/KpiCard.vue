<template>
    <article class="fin-card p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ title }}</p>
        <p class="mt-1 text-2xl font-bold text-slate-900">{{ value }}</p>

        <div class="mt-1 inline-flex items-center gap-1 text-xs font-semibold" :class="deltaType === 'up' ? 'text-emerald-600' : 'text-rose-600'">
            <span>{{ deltaType === 'up' ? '▲' : '▼' }}</span>
            <span>{{ delta }}</span>
        </div>

        <SparkLine v-if="sparklineData.length" class="mt-3" :data="sparklineData" :color="deltaType === 'up' ? '#10b981' : '#ef4444'" />
    </article>
</template>

<script setup>
import SparkLine from '../charts/SparkLine.vue';

defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    delta: { type: String, default: '0.00%' },
    deltaType: { type: String, default: 'up' },
    sparklineData: { type: Array, default: () => [] },
});
</script>
