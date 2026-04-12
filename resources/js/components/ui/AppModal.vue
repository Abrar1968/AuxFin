<template>
    <Teleport to="body">
        <div v-if="modelValue" class="fixed inset-0 z-50 grid place-items-center p-4" @keydown.esc="close">
            <button type="button" class="absolute inset-0 bg-slate-950/45 backdrop-blur-[1px]" @click="close"></button>

            <section
                class="relative z-10 w-full rounded-2xl border border-slate-200 bg-white shadow-2xl"
                :class="sizeClass"
                role="dialog"
                aria-modal="true"
            >
                <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 class="text-base font-bold text-slate-900">{{ title }}</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100" @click="close">x</button>
                </header>

                <div class="max-h-[70vh] overflow-auto px-5 py-4">
                    <slot />
                </div>

                <footer v-if="$slots.footer" class="border-t border-slate-200 px-5 py-4">
                    <slot name="footer" />
                </footer>
            </section>
        </div>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: 'Dialog' },
    size: { type: String, default: 'md' },
});

const sizeClass = computed(() => {
    const map = {
        sm: 'max-w-md',
        md: 'max-w-2xl',
        lg: 'max-w-4xl',
        xl: 'max-w-5xl',
    };

    return map[props.size] ?? map.md;
});

function close() {
    emit('update:modelValue', false);
}
</script>
