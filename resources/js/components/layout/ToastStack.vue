<template>
    <Teleport to="body">
        <div class="fixed right-4 top-4 z-[100] flex w-[min(92vw,380px)] flex-col gap-2 pointer-events-none">
            <TransitionGroup name="toast">
                <article
                    v-for="toast in toastStore.toasts"
                    :key="toast.id"
                    class="pointer-events-auto rounded-xl border p-3 shadow-lg backdrop-blur-sm"
                    :class="colorClass(toast.type)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold">{{ toast.title }}</p>
                            <p class="text-xs mt-1 leading-5">{{ toast.message }}</p>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-semibold opacity-80 hover:opacity-100"
                            @click="toastStore.remove(toast.id)"
                        >
                            Dismiss
                        </button>
                    </div>
                </article>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { useToastStore } from '../../stores/toast.store';

const toastStore = useToastStore();

function colorClass(type) {
    return {
        success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
        error: 'border-rose-200 bg-rose-50 text-rose-900',
        warning: 'border-amber-200 bg-amber-50 text-amber-900',
        info: 'border-blue-200 bg-blue-50 text-blue-900',
    }[type] ?? 'border-slate-200 bg-white text-slate-900';
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.18s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.98);
}
</style>
