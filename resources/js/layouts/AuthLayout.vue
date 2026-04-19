<template>
    <main class="relative min-h-screen overflow-hidden px-4 py-8 md:px-8 md:py-10">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(980px_520px_at_-10%_-15%,rgba(2,132,199,0.22),transparent_60%),radial-gradient(900px_500px_at_105%_0%,rgba(15,118,110,0.18),transparent_58%),linear-gradient(180deg,#f4fbff,#edf7fc_54%,#eef8ff)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-40 bg-[linear-gradient(rgba(15,118,110,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(2,132,199,0.08)_1px,transparent_1px)] bg-size-[52px_52px]"></div>

        <section class="relative mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-6xl items-center">
            <div class="grid w-full gap-4 rounded-[28px] border border-sky-200/70 bg-white/72 p-3 shadow-[0_34px_90px_rgba(15,23,42,0.16)] backdrop-blur md:grid-cols-[1.08fr_.92fr] md:p-4">
                <aside class="auth-reveal order-2 relative overflow-hidden rounded-[22px] border border-cyan-500/45 bg-linear-to-br from-cyan-700 via-sky-700 to-teal-700 p-7 text-white md:order-1 md:p-9">
                    <div class="pointer-events-none absolute -left-16 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl auth-orb"></div>
                    <div class="pointer-events-none absolute -bottom-20 right-0 h-56 w-56 rounded-full bg-emerald-300/20 blur-2xl auth-orb auth-orb-delay"></div>

                    <div class="relative space-y-6">
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

                <article class="auth-reveal auth-reveal-delay-1 order-1 rounded-[22px] border border-slate-200/80 bg-white p-6 shadow-[0_20px_45px_rgba(15,23,42,0.08)] md:order-2 md:p-8">
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
