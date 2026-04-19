<template>
    <component :is="as" class="fin-card fin-section-enter" :class="[padded ? 'p-5 md:p-6' : '', elevated ? 'fin-card-panel' : '']">
        <slot />
    </component>
</template>

<script setup>
defineProps({
    as: {
        type: String,
        default: 'article',
    },
    padded: {
        type: Boolean,
        default: true,
    },
    elevated: {
        type: Boolean,
        default: false,
    },
});
</script>
