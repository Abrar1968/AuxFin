<template>
    <div class="flex items-center justify-between gap-3 text-sm">
        <p class="text-slate-600">Page {{ page }} of {{ totalPages }}</p>
        <div class="flex items-center gap-2">
            <button
                type="button"
                class="rounded-lg border border-slate-300 px-3 py-1.5 hover:bg-slate-100 disabled:opacity-60"
                :disabled="page <= 1"
                @click="$emit('update:page', page - 1)"
            >
                Previous
            </button>
            <button
                type="button"
                class="rounded-lg border border-slate-300 px-3 py-1.5 hover:bg-slate-100 disabled:opacity-60"
                :disabled="page >= totalPages"
                @click="$emit('update:page', page + 1)"
            >
                Next
            </button>
        </div>
    </div>
</template>

<script setup>
defineEmits(['update:page']);

defineProps({
    page: { type: Number, default: 1 },
    totalPages: { type: Number, default: 1 },
});
</script>
