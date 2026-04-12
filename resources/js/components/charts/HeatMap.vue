<template>
    <div class="grid grid-cols-7 gap-1">
        <div
            v-for="day in days"
            :key="day.date"
            class="h-8 rounded"
            :class="statusClass(day.status)"
            :title="`${day.date} - ${day.status}`"
        ></div>
    </div>
</template>

<script setup>
const props = defineProps({
    days: { type: Array, default: () => [] },
});

function statusClass(status) {
    if (status === 'present') return 'bg-emerald-400';
    if (status === 'late') return 'bg-amber-400';
    if (status === 'absent') return 'bg-rose-400';
    return 'bg-slate-300';
}
</script>
