<template>
    <div class="grid gap-4 md:grid-cols-2">
        <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <h4 class="text-sm font-bold text-emerald-800">Earnings</h4>
            <ul class="mt-2 space-y-1 text-sm">
                <li v-for="item in earnings" :key="item.label" class="flex items-center justify-between gap-3">
                    <span class="text-emerald-700">{{ item.label }}</span>
                    <strong class="text-emerald-900 mono">{{ money(item.value) }}</strong>
                </li>
            </ul>
        </section>

        <section class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <h4 class="text-sm font-bold text-rose-800">Deductions</h4>
            <ul class="mt-2 space-y-1 text-sm">
                <li v-for="item in deductions" :key="item.label" class="flex items-center justify-between gap-3">
                    <span class="text-rose-700">{{ item.label }}</span>
                    <strong class="text-rose-900 mono">{{ money(item.value) }}</strong>
                </li>
            </ul>
        </section>
    </div>
</template>

<script setup>
defineProps({
    earnings: { type: Array, default: () => [] },
    deductions: { type: Array, default: () => [] },
});

function money(value) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(value ?? 0));
}
</script>
