<template>
    <article class="fin-card p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ typeLabel }}</p>
                <h4 class="mt-1 text-sm font-bold text-slate-900">{{ subject }}</h4>
                <p class="mt-1 text-xs text-slate-500">{{ submittedAt }}</p>
            </div>
            <StatusBadge :status="status" />
        </div>

        <p class="mt-3 text-sm text-slate-700 line-clamp-3">{{ body }}</p>
        <slot />
    </article>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from '../../ui/StatusBadge.vue';

const props = defineProps({
    type: { type: String, default: 'general_hr' },
    subject: { type: String, default: '' },
    body: { type: String, default: '' },
    status: { type: String, default: 'open' },
    submittedAt: { type: String, default: '' },
});

const typeLabel = computed(() => String(props.type ?? 'general_hr').replaceAll('_', ' '));
</script>
