<template>
    <article class="rounded-xl border p-4" :class="critical ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50'">
        <div class="flex items-center justify-between gap-3">
            <h4 class="text-sm font-bold" :class="critical ? 'text-rose-700' : 'text-amber-700'">{{ title }}</h4>
            <span class="text-xs font-semibold" :class="critical ? 'text-rose-700' : 'text-amber-700'">
                {{ critical ? 'Critical' : 'Watch' }}
            </span>
        </div>
        <p class="mt-2 text-sm" :class="critical ? 'text-rose-700' : 'text-amber-700'">{{ description }}</p>
    </article>
</template>

<script setup>
defineProps({
    title: { type: String, default: 'Anomaly detected' },
    description: { type: String, default: 'Review this metric for unusual behavior.' },
    critical: { type: Boolean, default: false },
});
</script>
