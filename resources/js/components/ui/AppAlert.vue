<template>
    <div class="rounded-xl border px-4 py-3 text-sm shadow-sm" :class="classes">
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
        success: 'border-emerald-200 bg-emerald-50/90 text-emerald-800',
        warning: 'border-amber-200 bg-amber-50/90 text-amber-800',
        error: 'border-rose-200 bg-rose-50/90 text-rose-800',
        info: 'border-sky-200 bg-sky-50/90 text-sky-800',
    };

    return map[props.type] ?? map.info;
});
</script>
