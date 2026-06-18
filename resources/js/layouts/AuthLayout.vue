<template>
    <main class="min-h-screen bg-[var(--bg-base)] px-4 py-8 md:px-8 md:py-10">
        <section class="relative mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-6xl items-center">
            <div class="grid w-full gap-4 rounded-[var(--radius-lg)] border border-[color:var(--border)] bg-[var(--bg-surface)] p-3 shadow-[var(--shadow-panel)] md:grid-cols-[1.08fr_.92fr] md:p-4">
                <aside class="auth-reveal order-2 rounded-[var(--radius-md)] border border-[color:var(--border)] bg-[var(--bg-dark)] p-7 text-white md:order-1 md:p-9">
                    <div class="space-y-6">
                        <p class="inline-flex items-center rounded-full border border-white/25 bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.13em] text-cyan-50">
                            Enterprise Financial Platform
                        </p>

                        <div class="space-y-3">
                            <h2 class="text-2xl font-extrabold leading-tight md:text-3xl">
                                Operate finance, payroll, and workforce workflows with confidence.
                            </h2>
                            <p class="max-w-xl text-sm text-cyan-50/92 md:text-base">
                                AuxFin unifies financial operations, employee lifecycle, and executive analytics in one secure operations center.
                            </p>
                        </div>

                        <ul class="space-y-3 text-sm text-cyan-50/95">
                            <li class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                                Role-based access controls for finance and HR operations.
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full bg-sky-200"></span>
                                Structured payroll, loans, and compliance-ready audit records.
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full bg-cyan-100"></span>
                                Real-time insights for leadership and operating teams.
                            </li>
                        </ul>
                    </div>
                </aside>

                <article class="auth-reveal auth-reveal-delay-1 order-1 rounded-[var(--radius-md)] border border-[color:var(--border)] bg-[var(--bg-surface)] p-6 shadow-[var(--shadow-card)] md:order-2 md:p-8">
                    <div class="mb-6 flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-slate-700">
                            <img :src="logoUrl" alt="AuxFin logo" class="h-6 w-6 rounded-full object-cover">
                            <span class="text-xs font-semibold uppercase tracking-[0.12em]">AuxFin Secure Access</span>
                        </div>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Version 2.0</span>
                    </div>

                    <RouterView />

                    <p class="mt-6 border-t border-slate-100 pt-4 text-xs text-slate-500">
                        Need access support? Contact your system administrator.
                    </p>
                </article>
            </div>
        </section>
    </main>
</template>

<script setup>
import { RouterView } from 'vue-router';

const logoUrl = '/images/logo.jpg';
</script>
