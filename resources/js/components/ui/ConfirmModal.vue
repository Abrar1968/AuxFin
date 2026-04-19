<template>
    <AppModal :model-value="modelValue" :title="title" size="sm" @update:modelValue="emit('update:modelValue', $event)">
        <p class="text-sm text-slate-700">{{ message }}</p>

        <template #footer>
            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                    :disabled="busy"
                    @click="emit('update:modelValue', false)"
                >
                    {{ cancelText }}
                </button>
                <button
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white"
                    :class="confirmClass"
                    :disabled="busy"
                    @click="emit('confirm')"
                >
                    {{ confirmText }}
                </button>
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { computed } from 'vue';
import AppModal from './AppModal.vue';

const emit = defineEmits(['update:modelValue', 'confirm']);

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: 'Please Confirm' },
    message: { type: String, default: 'Are you sure you want to continue?' },
    confirmText: { type: String, default: 'Confirm' },
    cancelText: { type: String, default: 'Cancel' },
    tone: { type: String, default: 'danger' },
    busy: { type: Boolean, default: false },
});

const confirmClass = computed(() => {
    const map = {
        danger: 'bg-rose-600',
        warning: 'bg-amber-600',
        success: 'bg-emerald-600',
        primary: 'bg-slate-900',
    };

    return map[props.tone] ?? map.danger;
});
</script>
