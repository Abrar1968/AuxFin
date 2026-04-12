<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="fin-focus-ring inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition duration-200"
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
        primary: 'bg-[image:var(--color-gradient)] text-white shadow-[0_10px_24px_rgba(2,132,199,.35)] hover:-translate-y-px hover:brightness-110 disabled:brightness-95',
        secondary: 'border border-sky-200 bg-white text-slate-700 shadow-sm hover:-translate-y-px hover:bg-sky-50',
        danger: 'bg-rose-600 text-white shadow-[0_10px_24px_rgba(225,29,72,.32)] hover:-translate-y-px hover:bg-rose-700',
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
