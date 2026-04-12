<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition"
        :class="classes"
    >
        <span v-if="loading" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/80 border-r-transparent"></span>
        <slot>{{ label }}</slot>
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    label: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const classes = computed(() => {
    const variants = {
        primary: 'bg-[image:var(--color-gradient)] text-white hover:brightness-110 disabled:brightness-95',
        secondary: 'border border-indigo-300 bg-white text-indigo-700 hover:bg-indigo-50',
        danger: 'bg-rose-600 text-white hover:bg-rose-700',
        ghost: 'bg-transparent text-slate-700 hover:bg-slate-100',
    };

    const sizes = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-5 py-2.5 text-base',
    };

    return `${variants[props.variant] ?? variants.primary} ${sizes[props.size] ?? sizes.md} ${props.disabled || props.loading ? 'opacity-70 cursor-not-allowed' : ''}`;
});
</script>
