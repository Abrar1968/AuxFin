<template>
    <section class="print-page mx-auto max-w-7xl space-y-5 bg-white p-5 text-slate-900">
        <header class="space-y-3 border-b border-slate-200 pb-4">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Print Friendly View</p>
            <h1 class="text-2xl font-black text-slate-900">{{ manualTitle }}</h1>
            <p class="text-sm text-slate-600">Generated: {{ generatedAt }}</p>

            <div class="print-hidden flex flex-wrap gap-2">
                <RouterLink
                    :to="{ name: 'admin.docs' }"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                >
                    Back To Docs
                </RouterLink>
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                    @click="downloadManual"
                >
                    Download PDF
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                    @click="printManual"
                >
                    Print Now
                </button>
            </div>
        </header>

        <article class="space-y-3">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Section Workflow Matrix</h2>

            <div class="overflow-x-auto">
                <table class="print-table w-full min-w-240 border border-slate-200 text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="border border-slate-200 p-2 font-semibold">Section</th>
                            <th class="border border-slate-200 p-2 font-semibold">Route</th>
                            <th class="border border-slate-200 p-2 font-semibold">Purpose</th>
                            <th class="border border-slate-200 p-2 font-semibold">Workflow</th>
                            <th class="border border-slate-200 p-2 font-semibold">Demo Input</th>
                            <th class="border border-slate-200 p-2 font-semibold">Expected Output</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="section in sections" :key="section.name" class="align-top">
                            <td class="border border-slate-200 p-2 font-semibold">{{ section.name }}</td>
                            <td class="border border-slate-200 p-2">{{ section.route }}</td>
                            <td class="border border-slate-200 p-2">{{ section.purpose }}</td>
                            <td class="border border-slate-200 p-2">
                                <ul class="list-disc space-y-1 pl-4">
                                    <li v-for="step in section.workflow" :key="`${section.name}-wf-${step}`">{{ step }}</li>
                                </ul>
                            </td>
                            <td class="border border-slate-200 p-2">
                                <ul class="list-disc space-y-1 pl-4">
                                    <li v-for="item in section.demo_input" :key="`${section.name}-in-${item}`">{{ item }}</li>
                                </ul>
                            </td>
                            <td class="border border-slate-200 p-2">
                                <ul class="list-disc space-y-1 pl-4">
                                    <li v-for="item in section.expected_output" :key="`${section.name}-out-${item}`">{{ item }}</li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="space-y-3">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Equation Matrix</h2>

            <div class="overflow-x-auto">
                <table class="print-table w-full min-w-205 border border-slate-200 text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="border border-slate-200 p-2 font-semibold">Equation</th>
                            <th class="border border-slate-200 p-2 font-semibold">Formula</th>
                            <th class="border border-slate-200 p-2 font-semibold">Demo Input</th>
                            <th class="border border-slate-200 p-2 font-semibold">Demo Output</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="equation in equations" :key="equation.name" class="align-top">
                            <td class="border border-slate-200 p-2 font-semibold">{{ equation.name }}</td>
                            <td class="border border-slate-200 p-2 font-mono">{{ equation.formula }}</td>
                            <td class="border border-slate-200 p-2">{{ equation.demo_input }}</td>
                            <td class="border border-slate-200 p-2">{{ equation.demo_output }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="space-y-3">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Analytics Playbook Matrix</h2>

            <div class="overflow-x-auto">
                <table class="print-table w-full min-w-240 border border-slate-200 text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="border border-slate-200 p-2 font-semibold">Module</th>
                            <th class="border border-slate-200 p-2 font-semibold">Metric</th>
                            <th class="border border-slate-200 p-2 font-semibold">Route</th>
                            <th class="border border-slate-200 p-2 font-semibold">Chart Type</th>
                            <th class="border border-slate-200 p-2 font-semibold">Formula</th>
                            <th class="border border-slate-200 p-2 font-semibold">How To Use</th>
                            <th class="border border-slate-200 p-2 font-semibold">Decision Guide</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="metric in analyticsMetrics" :key="metric.id" class="align-top">
                            <td class="border border-slate-200 p-2 font-semibold">{{ metric.module }}</td>
                            <td class="border border-slate-200 p-2">{{ metric.metric }}</td>
                            <td class="border border-slate-200 p-2">{{ metric.route }}</td>
                            <td class="border border-slate-200 p-2">{{ metric.chart }}</td>
                            <td class="border border-slate-200 p-2 font-mono">{{ metric.formula }}</td>
                            <td class="border border-slate-200 p-2">
                                <ul class="list-disc space-y-1 pl-4">
                                    <li v-for="line in metric.how_to_use" :key="`${metric.id}-use-${line}`">{{ line }}</li>
                                </ul>
                            </td>
                            <td class="border border-slate-200 p-2">
                                <ul class="list-disc space-y-1 pl-4">
                                    <li v-for="line in metric.decision_guide" :key="`${metric.id}-decision-${line}`">{{ line }}</li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import { exportAdminManualPdf } from '../../../utils/report-pdf';
import {
    adminManualAnalyticsPlaybook,
    adminManualEquations,
    adminManualSections,
    adminManualTitle,
} from './admin-manual.data';

const sections = adminManualSections;
const equations = adminManualEquations;
const analyticsMetrics = adminManualAnalyticsPlaybook;
const manualTitle = adminManualTitle;
const generatedAt = new Date().toLocaleString();

function downloadManual() {
    exportAdminManualPdf({
        title: manualTitle,
        generated_at: new Date().toISOString(),
        sections,
        equations,
        analytics_metrics: analyticsMetrics,
    });
}

function printManual() {
    window.print();
}
</script>

<style scoped>
@media print {
    .print-hidden {
        display: none !important;
    }

    .print-page {
        max-width: none;
        padding: 0;
    }

    .print-table tr {
        break-inside: avoid;
    }
}
</style>
