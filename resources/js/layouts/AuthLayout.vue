<template>
    <main class="min-h-screen grid place-items-center px-4 py-8">
        <section class="w-full max-w-md rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
            <div class="h-24 bg-[image:var(--color-gradient)]"></div>
            <div class="p-6">
                <RouterView />
            </div>
        </section>
    </main>
</template>

<script setup>
import { RouterView } from 'vue-router';
</script>
