<template>
    <section class="space-y-5">
        <header class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Employee Channel</p>
                    <h1 class="text-2xl font-black text-slate-900">Employee Self-Service Manual</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Role-focused manual for employee portal usage with stepwise actions, demo inputs, expected outputs, and salary or attendance equations.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <RouterLink
                        :to="{ name: 'employee.docs.print' }"
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
                    <p class="font-semibold text-slate-800">1. Complete Profile Read</p>
                    <p class="mt-1 text-slate-600">Open each section in sequence to understand where each personal finance detail is shown.</p>
                </div>
                <div class="rounded-lg bg-slate-100 p-3 text-sm">
                    <p class="font-semibold text-slate-800">2. Use Demo Checks</p>
                    <p class="mt-1 text-slate-600">Match your table values with the expected output pattern listed in every section card.</p>
                </div>
                <div class="rounded-lg bg-slate-100 p-3 text-sm">
                    <p class="font-semibold text-slate-800">3. Keep Offline Copy</p>
                    <p class="mt-1 text-slate-600">Download the employee manual PDF for HR induction, quick support, or compliance records.</p>
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
                    placeholder="Search sections, route paths, workflow steps, or equations"
                >

                <select v-model="filterScope" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="all">Show All</option>
                    <option value="sections">Sections Only</option>
                    <option value="equations">Equations Only</option>
                </select>

                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 disabled:opacity-50"
                    :disabled="searchTerm.length === 0 && filterScope === 'all'"
                    @click="clearFilters"
                >
                    Reset Filters
                </button>
            </div>

            <p class="mt-3 text-xs text-slate-600">
                Showing {{ filteredSections.length }} of {{ sections.length }} sections and {{ filteredEquations.length }} of {{ equations.length }} equations.
            </p>
        </article>

        <article v-if="showSections" class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Portal Usage Guide</h2>
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
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useToastStore } from '../../../stores/toast.store';
import { exportEmployeeManualPdf } from '../../../utils/report-pdf';
import { employeeManualEquations, employeeManualSections, employeeManualTitle } from './employee-manual.data';

const toast = useToastStore();

const sections = employeeManualSections;
const equations = employeeManualEquations;

const searchTerm = ref('');
const filterScope = ref('all');

const query = computed(() => searchTerm.value.trim().toLowerCase());
const showSections = computed(() => filterScope.value !== 'equations');
const showEquations = computed(() => filterScope.value !== 'sections');

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

function clearFilters() {
    searchTerm.value = '';
    filterScope.value = 'all';
}

function downloadManual() {
    exportEmployeeManualPdf({
        title: employeeManualTitle,
        generated_at: new Date().toISOString(),
        sections: filteredSections.value,
        equations: filteredEquations.value,
    });

    toast.success('Employee docs manual PDF export started.');
}
</script>
