<template>
    <section class="space-y-5">
        <header class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Superadmin And Admin Channel</p>
                    <h1 class="text-2xl font-black text-slate-900">Operations Docs Manual</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Complete walkthrough for each admin section with workflow, demo inputs, expected outputs, and an interactive analytics playbook.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <RouterLink
                        :to="{ name: 'admin.docs.print' }"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                    >
                        Open Print View
                    </RouterLink>
                    <button
                        type="button"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                        @click="downloadManual"
                    >
                        Download Manual PDF
                    </button>
                </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-lg bg-slate-100 p-3 text-sm">
                    <p class="font-semibold text-slate-800">1. Follow Section Workflow</p>
                    <p class="mt-1 text-slate-600">Use the listed workflow before entering demo values to preserve data integrity.</p>
                </div>
                <div class="rounded-lg bg-slate-100 p-3 text-sm">
                    <p class="font-semibold text-slate-800">2. Validate Outputs</p>
                    <p class="mt-1 text-slate-600">Cross-check cards, tables, and formulas against expected outputs in this manual.</p>
                </div>
                <div class="rounded-lg bg-slate-100 p-3 text-sm">
                    <p class="font-semibold text-slate-800">3. Use Analytics Playbook</p>
                    <p class="mt-1 text-slate-600">Open Interactive Analytics Playbook below to understand each chart, meaning, and action plan.</p>
                </div>
            </div>
        </header>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Search And Filter</h2>

            <div class="mt-3 grid gap-3 md:grid-cols-[1fr_auto_auto]">
                <input
                    v-model="searchTerm"
                    type="text"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Search sections, route paths, workflow, equations, analytics metrics, or chart types"
                >

                <select v-model="filterScope" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="all">Show All</option>
                    <option value="sections">Sections Only</option>
                    <option value="equations">Equations Only</option>
                    <option value="analytics">Analytics Only</option>
                </select>

                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 disabled:opacity-50"
                    :disabled="searchTerm.length === 0 && filterScope === 'all' && analyticsModule === 'all'"
                    @click="clearFilters"
                >
                    Reset Filters
                </button>
            </div>

            <p class="mt-3 text-xs text-slate-600">
                Showing {{ filteredSections.length }} of {{ sections.length }} sections,
                {{ filteredEquations.length }} of {{ equations.length }} equations, and
                {{ filteredAnalyticsMetrics.length }} of {{ analyticsMetrics.length }} analytics metrics.
            </p>
        </article>

        <article v-if="showAnalytics" class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Interactive Analytics Playbook</h2>
                <p class="text-xs text-slate-600">Matched Metrics: {{ filteredAnalyticsMetrics.length }}</p>
            </div>

            <p class="mt-2 text-sm text-slate-600">
                Pick any metric to see what it measures, where it appears, which chart type is used, how to use it in decisions, and test values with a live scenario calculator.
            </p>

            <div class="mt-4 grid gap-4 lg:grid-cols-[19rem_1fr]">
                <aside class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <label class="text-xs font-semibold uppercase tracking-[0.11em] text-slate-500">Analytics Module</label>
                    <select v-model="analyticsModule" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option v-for="option in analyticsModules" :key="option" :value="option">
                            {{ option === 'all' ? 'All Modules' : option }}
                        </option>
                    </select>

                    <div class="mt-3 max-h-80 space-y-2 overflow-y-auto pr-1">
                        <button
                            v-for="metric in filteredAnalyticsMetrics"
                            :key="metric.id"
                            type="button"
                            class="w-full rounded-lg border px-3 py-2 text-left text-xs font-semibold transition"
                            :class="selectedAnalyticsMetricId === metric.id
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400'"
                            @click="selectMetric(metric.id)"
                        >
                            <p class="truncate">{{ metric.metric }}</p>
                            <p class="mt-1 truncate text-[10px]" :class="selectedAnalyticsMetricId === metric.id ? 'text-slate-200' : 'text-slate-500'">
                                {{ metric.module }}
                            </p>
                        </button>

                        <p v-if="filteredAnalyticsMetrics.length === 0" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                            No analytics metric matches the current search or module filter.
                        </p>
                    </div>
                </aside>

                <div v-if="activeAnalyticsMetric" class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-slate-900">{{ activeAnalyticsMetric.metric }}</h3>
                        <span class="rounded-full border border-slate-300 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600">
                            {{ activeAnalyticsMetric.route }}
                        </span>
                    </div>

                    <p class="mt-2 text-sm text-slate-700">{{ activeAnalyticsMetric.what_it_shows }}</p>

                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.11em] text-slate-500">Module</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ activeAnalyticsMetric.module }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.11em] text-slate-500">Chart Type</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ activeAnalyticsMetric.chart }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.11em] text-slate-500">Data Source</p>
                            <p class="mt-1 text-sm text-slate-700">{{ activeAnalyticsMetric.data_source }}</p>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.11em] text-slate-500">How To Use</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                <li v-for="line in activeAnalyticsMetric.how_to_use" :key="`${activeAnalyticsMetric.id}-use-${line}`">- {{ line }}</li>
                            </ul>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.11em] text-slate-500">Decision Guide</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                <li v-for="line in activeAnalyticsMetric.decision_guide" :key="`${activeAnalyticsMetric.id}-decision-${line}`">- {{ line }}</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-bold uppercase tracking-[0.11em] text-slate-500">Formula</p>
                        <p class="mt-2 font-mono text-xs text-slate-700">{{ activeAnalyticsMetric.formula }}</p>
                    </div>

                    <div v-if="(activeAnalyticsMetric.calculator_inputs ?? []).length > 0" class="mt-3 rounded-lg border border-slate-200 bg-white p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs font-bold uppercase tracking-[0.11em] text-slate-500">Scenario Calculator</p>
                            <button
                                type="button"
                                class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700"
                                @click="resetCalculator"
                            >
                                Reset Values
                            </button>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-3">
                            <label
                                v-for="input in activeAnalyticsMetric.calculator_inputs"
                                :key="`${activeAnalyticsMetric.id}-${input.key}`"
                                class="block"
                            >
                                <span class="text-xs font-semibold text-slate-600">{{ input.label }}</span>
                                <input
                                    v-model.number="calculatorInputs[input.key]"
                                    type="number"
                                    :min="input.min ?? 0"
                                    :step="input.step ?? 'any'"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                >
                            </label>
                        </div>

                        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.11em] text-slate-500">
                                {{ activeAnalyticsMetric.result_label ?? 'Result' }}
                            </p>
                            <p class="mt-1 text-lg font-black text-slate-900">{{ calculatorResult?.display ?? '0.00' }}</p>
                            <p class="mt-1 text-xs text-slate-600">{{ calculatorResult?.hint ?? 'Adjust inputs to test scenarios.' }}</p>
                        </div>
                    </div>
                </div>

                <p v-else class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    No analytics metric matches the current filter.
                </p>
            </div>
        </article>

        <article v-if="showSections" class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Section Usage Guide</h2>
                <p class="text-xs text-slate-600">Matched Sections: {{ filteredSections.length }}</p>
            </div>

            <p v-if="filteredSections.length === 0" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                No section matches the current filter.
            </p>

            <div v-else class="mt-4 space-y-4">
                <div
                    v-for="section in filteredSections"
                    :key="section.name"
                    class="rounded-xl border border-slate-200 bg-slate-50/60 p-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-slate-900">{{ section.name }}</h3>
                        <span class="rounded-full border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">
                            {{ section.route }}
                        </span>
                    </div>

                    <p class="mt-2 text-sm text-slate-700">{{ section.purpose }}</p>

                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Workflow</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                <li v-for="step in section.workflow" :key="`${section.name}-wf-${step}`">- {{ step }}</li>
                            </ul>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Demo Input</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                <li v-for="input in section.demo_input" :key="`${section.name}-in-${input}`">- {{ input }}</li>
                            </ul>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Expected Output</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                <li v-for="output in section.expected_output" :key="`${section.name}-out-${output}`">- {{ output }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <article v-if="showEquations" class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Equation Demo Input And Output</h2>
                <p class="text-xs text-slate-600">Matched Equations: {{ filteredEquations.length }}</p>
            </div>

            <p v-if="filteredEquations.length === 0" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                No equation matches the current filter.
            </p>

            <div v-else class="mt-4 overflow-x-auto">
                <table class="w-full min-w-205 text-left text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="p-3 font-semibold">Equation</th>
                            <th class="p-3 font-semibold">Formula</th>
                            <th class="p-3 font-semibold">Demo Input</th>
                            <th class="p-3 font-semibold">Demo Output</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="equation in filteredEquations" :key="equation.name" class="border-t border-slate-200 align-top">
                            <td class="p-3 font-semibold text-slate-800">{{ equation.name }}</td>
                            <td class="p-3 font-mono text-xs text-slate-700">{{ equation.formula }}</td>
                            <td class="p-3 text-slate-700">{{ equation.demo_input }}</td>
                            <td class="p-3 text-slate-700">{{ equation.demo_output }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useToastStore } from '../../../stores/toast.store';
import { exportAdminManualPdf } from '../../../utils/report-pdf';
import {
    adminManualAnalyticsPlaybook,
    adminManualEquations,
    adminManualSections,
    adminManualTitle,
} from './admin-manual.data';

const toast = useToastStore();

const sections = adminManualSections;
const equations = adminManualEquations;
const analyticsMetrics = adminManualAnalyticsPlaybook;

const searchTerm = ref('');
const filterScope = ref('all');
const analyticsModule = ref('all');
const selectedAnalyticsMetricId = ref('');
const calculatorInputs = ref({});

const query = computed(() => searchTerm.value.trim().toLowerCase());
const showSections = computed(() => filterScope.value !== 'equations' && filterScope.value !== 'analytics');
const showEquations = computed(() => filterScope.value !== 'sections' && filterScope.value !== 'analytics');
const showAnalytics = computed(() => filterScope.value !== 'sections' && filterScope.value !== 'equations');

const analyticsModules = computed(() => {
    const modules = Array.from(new Set(analyticsMetrics.map((metric) => metric.module)));
    return ['all', ...modules];
});

const filteredSections = computed(() => {
    if (!query.value) {
        return sections;
    }

    return sections.filter((section) => {
        const haystack = [
            section.name,
            section.route,
            section.purpose,
            ...(section.workflow ?? []),
            ...(section.demo_input ?? []),
            ...(section.expected_output ?? []),
        ]
            .join(' ')
            .toLowerCase();

        return haystack.includes(query.value);
    });
});

const filteredEquations = computed(() => {
    if (!query.value) {
        return equations;
    }

    return equations.filter((equation) => {
        const haystack = [equation.name, equation.formula, equation.demo_input, equation.demo_output]
            .join(' ')
            .toLowerCase();

        return haystack.includes(query.value);
    });
});

const filteredAnalyticsMetrics = computed(() => {
    return analyticsMetrics.filter((metric) => {
        if (analyticsModule.value !== 'all' && metric.module !== analyticsModule.value) {
            return false;
        }

        if (!query.value) {
            return true;
        }

        const haystack = [
            metric.module,
            metric.metric,
            metric.route,
            metric.chart,
            metric.formula,
            metric.data_source,
            metric.what_it_shows,
            ...(metric.how_to_use ?? []),
            ...(metric.decision_guide ?? []),
        ]
            .join(' ')
            .toLowerCase();

        return haystack.includes(query.value);
    });
});

const activeAnalyticsMetric = computed(() => {
    if (filteredAnalyticsMetrics.value.length === 0) {
        return null;
    }

    return filteredAnalyticsMetrics.value.find((metric) => metric.id === selectedAnalyticsMetricId.value) ?? filteredAnalyticsMetrics.value[0];
});

watch(
    filteredAnalyticsMetrics,
    (metrics) => {
        if (metrics.length === 0) {
            selectedAnalyticsMetricId.value = '';
            return;
        }

        const isSelectedValid = metrics.some((metric) => metric.id === selectedAnalyticsMetricId.value);
        if (!isSelectedValid) {
            selectedAnalyticsMetricId.value = metrics[0].id;
        }
    },
    { immediate: true },
);

watch(
    activeAnalyticsMetric,
    (metric) => {
        if (!metric) {
            calculatorInputs.value = {};
            return;
        }

        const next = {};
        for (const input of metric.calculator_inputs ?? []) {
            next[input.key] = Number(input.default_value ?? 0);
        }
        calculatorInputs.value = next;
    },
    { immediate: true },
);

const calculatorResult = computed(() => {
    const metric = activeAnalyticsMetric.value;
    if (!metric?.calculator_type) {
        return null;
    }

    const valueOf = (key) => Number(calculatorInputs.value[key] ?? 0);

    if (metric.calculator_type === 'runway') {
        const availableCash = valueOf('available_cash');
        const burnRate = valueOf('burn_rate');
        const runway = burnRate > 0 ? availableCash / burnRate : 0;

        return {
            display: `${formatNumber(runway)} months`,
            hint: burnRate > 0
                ? `At current burn, cash lasts roughly ${formatNumber(runway)} months.`
                : 'Burn rate must be greater than zero.',
        };
    }

    if (metric.calculator_type === 'ar_health') {
        const bucket0_30 = valueOf('bucket_0_30');
        const bucket31_60 = valueOf('bucket_31_60');
        const bucket61_90 = valueOf('bucket_61_90');
        const bucket90Plus = valueOf('bucket_90_plus');

        const weighted = (bucket0_30 * 0.95) + (bucket31_60 * 0.8) + (bucket61_90 * 0.6) + (bucket90Plus * 0.3);
        const total = bucket0_30 + bucket31_60 + bucket61_90 + bucket90Plus;
        const score = total > 0 ? (weighted / total) * 100 : 0;
        const status = score >= 90 ? 'excellent' : score >= 70 ? 'good' : score >= 50 ? 'watch' : 'critical';

        return {
            display: `${formatNumber(score)}% (${status})`,
            hint: `Higher 0-30 day share improves AR health quality.`,
        };
    }

    if (metric.calculator_type === 'cmgr') {
        const initial = valueOf('initial_value');
        const final = valueOf('final_value');
        const months = valueOf('months');
        const cmgr = initial > 0 && final > 0 && months > 0
            ? (Math.pow(final / initial, 1 / months) - 1) * 100
            : 0;

        return {
            display: `${formatNumber(cmgr)}%`,
            hint: 'Compare CMGR values across revenue and cost metrics before scaling decisions.',
        };
    }

    if (metric.calculator_type === 'revenue_quality') {
        const revenue = valueOf('revenue');
        const accountsReceivable = valueOf('accounts_receivable');
        const quality = revenue > 0 ? ((revenue - accountsReceivable) / revenue) * 100 : 0;

        return {
            display: `${formatNumber(quality)}%`,
            hint: 'Lower values indicate weaker conversion from recognized revenue to collected cash.',
        };
    }

    if (metric.calculator_type === 'payroll_ratio') {
        const totalPayroll = valueOf('total_payroll');
        const totalRevenue = valueOf('total_revenue');
        const headcount = Math.max(1, valueOf('headcount'));
        const payrollRatio = totalRevenue > 0 ? (totalPayroll / totalRevenue) * 100 : 0;
        const revenuePerEmployee = totalRevenue / headcount;

        return {
            display: `${formatNumber(payrollRatio)}%`,
            hint: `Revenue per employee: ${formatNumber(revenuePerEmployee)}.`,
        };
    }

    if (metric.calculator_type === 'z_score') {
        const value = valueOf('value');
        const mean = valueOf('mean');
        const stdDev = valueOf('std_dev');
        const zScore = stdDev > 0 ? (value - mean) / stdDev : 0;
        const flag = Math.abs(zScore) > 2.5 ? 'anomaly likely' : 'normal range';

        return {
            display: `${formatNumber(zScore, 4)} (${flag})`,
            hint: 'Absolute z-score above 2.5 should be investigated.',
        };
    }

    return null;
});

function formatNumber(value, decimals = 2) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(Number(value ?? 0));
}

function selectMetric(metricId) {
    selectedAnalyticsMetricId.value = metricId;
}

function clearFilters() {
    searchTerm.value = '';
    filterScope.value = 'all';
    analyticsModule.value = 'all';
}

function resetCalculator() {
    const metric = activeAnalyticsMetric.value;
    if (!metric) {
        calculatorInputs.value = {};
        return;
    }

    const resetValues = {};
    for (const input of metric.calculator_inputs ?? []) {
        resetValues[input.key] = Number(input.default_value ?? 0);
    }

    calculatorInputs.value = resetValues;
}

function downloadManual() {
    exportAdminManualPdf({
        title: adminManualTitle,
        generated_at: new Date().toISOString(),
        sections: filteredSections.value,
        equations: filteredEquations.value,
        analytics_metrics: filteredAnalyticsMetrics.value,
    });

    toast.success('Admin docs manual PDF export started.');
}
</script>
