<template>
    <main class="min-h-screen relative overflow-hidden px-4 py-10">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(700px_420px_at_15%_12%,rgba(79,70,229,0.35),transparent_60%),radial-gradient(880px_520px_at_85%_0%,rgba(124,58,237,0.26),transparent_62%),linear-gradient(145deg,#1e1b4b,#312e81,#0f172a)]"></div>

        <section class="relative mx-auto w-full max-w-md rounded-[22px] border border-white/25 bg-white/96 shadow-[0_24px_50px_rgba(15,23,42,0.35)] overflow-hidden">
            <div class="h-24 bg-(image:--color-gradient) flex items-center justify-center">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/20 px-4 py-2 text-white">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white text-indigo-700 font-black text-xs">F</span>
                    <span class="font-semibold tracking-wide">FinERP Secure</span>
                </div>
            </div>
            <div class="p-6 md:p-7">
                <RouterView />
            </div>
        </section>
    </main>
</template>

<script setup>
import { RouterView } from 'vue-router';
</script>
