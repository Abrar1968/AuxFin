<template>
    <article class="rounded-xl border p-4 shadow-[0_4px_14px_rgba(15,23,42,.05)]" :class="critical ? 'border-rose-200 bg-rose-50/80' : 'border-amber-200 bg-amber-50/80'">
        <div class="flex items-center justify-between gap-3">
            <h4 class="text-sm font-bold" :class="critical ? 'text-rose-700' : 'text-amber-700'">{{ title }}</h4>
            <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold" :class="critical ? 'border-rose-300 bg-rose-100 text-rose-700' : 'border-amber-300 bg-amber-100 text-amber-700'">
                {{ critical ? 'Critical Risk' : 'Watchlist' }}
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
