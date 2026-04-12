<template>
    <label class="block space-y-1">
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ label }}</span>
        <input
            :value="modelValue"
            :type="type"
            :placeholder="placeholder"
            :disabled="disabled"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
            @input="$emit('update:modelValue', $event.target.value)"
        >
    </label>
</template>

<script setup>
defineEmits(['update:modelValue']);

defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});
</script>
