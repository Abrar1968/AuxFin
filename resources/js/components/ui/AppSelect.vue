<template>
    <label class="block space-y-1">
        <span class="text-xs font-semibold uppercase tracking-[0.11em] text-slate-600">{{ label }}</span>
        <select
            :value="modelValue"
            :disabled="disabled"
            class="fin-focus-ring w-full rounded-xl border border-sky-200/80 bg-white/90 px-3 py-2.5 text-sm text-slate-800 outline-none transition duration-200 focus:border-sky-400 focus:bg-white"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
    </label>
</template>

<script setup>
defineEmits(['update:modelValue']);

defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
});
</script>
