<template>
    <label class="block space-y-1">
        <span class="text-xs font-semibold uppercase tracking-[0.11em] text-slate-600">{{ label }}</span>
        <input
            :value="modelValue"
            :type="type"
            :name="name"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            :autocapitalize="autocapitalize"
            :spellcheck="spellcheck"
            :disabled="disabled"
            class="fin-focus-ring w-full rounded-xl border border-sky-200/80 bg-white/90 px-3 py-2.5 text-sm text-slate-800 shadow-[inset_0_1px_0_rgba(255,255,255,.8)] outline-none transition duration-200 placeholder:text-slate-400 focus:border-sky-400 focus:bg-white"
            @input="$emit('update:modelValue', $event.target.value)"
            @change="$emit('update:modelValue', $event.target.value)"
        >
    </label>
</template>

<script setup>
defineEmits(['update:modelValue']);

defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    name: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: undefined },
    autocapitalize: { type: String, default: 'off' },
    spellcheck: { type: [Boolean, String], default: false },
    disabled: { type: Boolean, default: false },
});
</script>
