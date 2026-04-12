<template>
    <article class="fin-card p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label }}</p>
        <p class="mt-1 text-xl font-extrabold" :class="deltaClass">{{ value }}%</p>
        <p class="mt-1 text-xs text-slate-500">{{ directionLabel }}</p>
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
const directionLabel = computed(() => (numeric.value >= 0 ? 'Trending up' : 'Trending down'));
</script>
