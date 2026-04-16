<template>
    <article class="fin-card h-full p-4">
        <div class="flex items-start justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label }}</p>
            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="badgeClass">
                {{ directionLabel }}
            </span>
        </div>

        <p class="mt-2 text-xl font-extrabold" :class="deltaClass">{{ numeric.toFixed(2) }}%</p>
        <p class="mt-1 text-xs text-slate-500">Compounded monthly growth signal</p>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], default: 0 },
});

const numeric = computed(() => Number(props.value ?? 0));
const deltaClass = computed(() => (numeric.value >= 0 ? 'text-emerald-600' : 'text-rose-600'));
const directionLabel = computed(() => (numeric.value >= 0 ? 'Positive' : 'Negative'));
const badgeClass = computed(() => (numeric.value >= 0
    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    : 'bg-rose-50 text-rose-700 border border-rose-200'));
</script>
