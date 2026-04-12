<template>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-100 text-slate-600">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        class="p-3 text-left font-semibold"
                    >
                        <button
                            v-if="column.sortable"
                            type="button"
                            class="inline-flex items-center gap-1"
                            @click="$emit('sort', column.key)"
                        >
                            {{ column.label }}
                            <span class="text-[10px]">⇅</span>
                        </button>
                        <span v-else>{{ column.label }}</span>
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr v-if="loading">
                    <td :colspan="columns.length" class="p-4 text-slate-500">Loading...</td>
                </tr>

                <tr v-for="(row, index) in rows" v-else :key="row[idKey] ?? index" class="border-t border-slate-100">
                    <td v-for="column in columns" :key="column.key" class="p-3 align-top">
                        <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]" :index="index">
                            {{ row[column.key] }}
                        </slot>
                    </td>
                </tr>

                <tr v-if="!loading && rows.length === 0">
                    <td :colspan="columns.length" class="p-4 text-slate-500">{{ emptyText }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
defineEmits(['sort']);

defineProps({
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    emptyText: { type: String, default: 'No records found.' },
    idKey: { type: String, default: 'id' },
});
</script>
