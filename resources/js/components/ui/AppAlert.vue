<template>
    <div class="rounded-xl border px-4 py-3 text-sm" :class="classes">
        <slot>{{ message }}</slot>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'info' },
    message: { type: String, default: '' },
});

const classes = computed(() => {
    const map = {
        success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        warning: 'border-amber-200 bg-amber-50 text-amber-700',
        error: 'border-rose-200 bg-rose-50 text-rose-700',
        info: 'border-blue-200 bg-blue-50 text-blue-700',
    };

    return map[props.type] ?? map.info;
});
</script>
