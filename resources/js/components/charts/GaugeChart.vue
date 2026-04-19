<template>
    <svg viewBox="0 0 200 120" class="w-full max-w-70 mx-auto">
        <path d="M20 100 A80 80 0 0 1 180 100" fill="none" stroke="#e2e8f0" stroke-width="16"></path>
        <path :d="needle" stroke="#0f172a" stroke-width="4" stroke-linecap="round"></path>
        <circle cx="100" cy="100" r="6" fill="#0f172a"></circle>
        <text x="100" y="75" text-anchor="middle" class="fill-slate-700 text-sm font-semibold">{{ value.toFixed(1) }}%</text>
    </svg>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
});

const needle = computed(() => {
    const v = Math.max(0, Math.min(100, Number(props.value)));
    const angle = Math.PI * (1 - v / 100);
    const x = 100 + 65 * Math.cos(angle);
    const y = 100 - 65 * Math.sin(angle);
    return `M100 100 L${x} ${y}`;
});
</script>
