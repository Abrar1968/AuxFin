<template>
    <div
        class="relative overflow-hidden rounded-lg bg-slate-200/80"
        :class="[heightClass, widthClass]"
        aria-hidden="true"
    >
        <div class="absolute inset-0 -translate-x-full bg-linear-to-r from-transparent via-white/70 to-transparent animate-shimmer"></div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    width: { type: String, default: 'full' },
    height: { type: String, default: '4' },
});

const widthClass = computed(() => (props.width === 'full' ? 'w-full' : `w-${props.width}`));
const heightClass = computed(() => `h-${props.height}`);
</script>

<style scoped>
@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}

.animate-shimmer {
    animation: shimmer 1.4s infinite;
}
</style>
