<template>
    <component :is="as" class="fin-card" :class="[padded ? 'p-5' : '', elevated ? 'fin-card-panel' : '']">
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
