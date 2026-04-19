<template>
    <svg viewBox="0 0 42 42" class="w-full max-w-55 mx-auto">
        <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="#e2e8f0" stroke-width="5"></circle>
        <circle
            cx="21"
            cy="21"
            r="15.915"
            fill="transparent"
            stroke="#2f6fed"
            stroke-width="5"
            :stroke-dasharray="`${ratio} ${100 - ratio}`"
            stroke-linecap="round"
            transform="rotate(-90 21 21)"
        ></circle>
        <text x="21" y="22" text-anchor="middle" class="fill-slate-700 text-[4px] font-semibold">{{ ratio.toFixed(1) }}%</text>
    </svg>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    total: { type: Number, default: 100 },
});

const ratio = computed(() => {
    if (props.total <= 0) return 0;
    return Math.max(0, Math.min(100, (props.value / props.total) * 100));
});
</script>
