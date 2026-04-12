<template>
    <div class="w-full rounded-full bg-slate-200 h-3 overflow-hidden">
        <div class="h-full bg-[image:var(--color-gradient)] transition-all" :style="{ width: `${safeValue}%` }"></div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
});

const safeValue = computed(() => Math.max(0, Math.min(100, Number(props.value))));
</script>
