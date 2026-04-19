<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">People Ops</p>
                <h1 class="text-2xl font-black text-slate-900">Employee Directory</h1>
                <p class="mt-1 text-sm text-slate-600">Manage workforce records, department structures, and account lifecycle controls.</p>
            </div>

            <RouterLink
                :to="{ name: 'admin.employee.create' }"
                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
            >
                Add Employee
            </RouterLink>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Employees</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ employeePagination.total }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Active</p>
                <p class="mt-2 text-2xl font-black text-emerald-800">{{ activeCount }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Inactive</p>
                <p class="mt-2 text-2xl font-black text-amber-800">{{ inactiveCount }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-700">Departments</p>
                <p class="mt-2 text-2xl font-black text-indigo-900">{{ departments.length }}</p>
            </article>
        </div>

        <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Search</label>
                <input
                    v-model="search"
                    class="mt-1 block rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Name, code, or email"
                >
            </div>

            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="refreshAll">Refresh</button>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
            <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Departments</h3>

            <form class="grid md:grid-cols-3 gap-3" @submit.prevent="createDepartment">
                <input
                    v-model="departmentForm.name"
                    required
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Department name"
                >
                <select v-model="departmentForm.head_id" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">No head assigned</option>
                    <option v-for="employee in rows" :key="employee.id" :value="employee.id">
                        {{ employee.employee_code }} - {{ employee.user?.name }}
                    </option>
                </select>
                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Save Department</button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="p-3 text-left">Department</th>
                            <th class="p-3 text-left">Head</th>
                            <th class="p-3 text-left">Employees</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="department in departments" :key="department.id" class="border-t border-slate-100">
                            <td class="p-3">{{ department.name }}</td>
                            <td class="p-3">{{ department.head?.user?.name ?? '-' }}</td>
                            <td class="p-3">{{ department.employees_count ?? 0 }}</td>
                            <td class="p-3 text-right space-x-3">
                                <button class="text-xs font-semibold text-amber-700" @click="openDepartmentEditModal(department)">Edit</button>
                                <button class="text-xs font-semibold text-rose-700" @click="openDepartmentDeleteModal(department.id)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="departments.length === 0">
                            <td colspan="4" class="p-3 text-center text-slate-500">No departments found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Employee Registry</h3>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Per page</span>
                        <select v-model.number="employeeFilters.per_page" class="rounded-lg border border-slate-300 px-2 py-1" @change="onEmployeePerPageChange">
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                        </select>
                    </div>
                </div>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="p-3 text-left">Code</th>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Designation</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="p-3 font-semibold">{{ row.employee_code }}</td>
                        <td class="p-3">{{ row.user?.name }}</td>
                        <td class="p-3">{{ row.user?.email }}</td>
                        <td class="p-3">{{ row.designation }}</td>
                        <td class="p-3">
                            <StatusBadge :status="row.user?.is_active ? 'active' : 'inactive'" :label="row.user?.is_active ? 'active' : 'inactive'" />
                        </td>
                        <td class="space-x-3 p-3 text-right">
                            <RouterLink
                                :to="{ name: 'admin.employee.detail', params: { id: row.id } }"
                                class="text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                            >
                                View
                            </RouterLink>
                            <button class="text-xs font-semibold text-blue-700" @click="resetPasskey(row.id)">Reset Passkey</button>
                            <button class="text-xs font-semibold text-rose-700" @click="removeEmployee(row.id)">Delete</button>
                        </td>
                    </tr>

                    <tr v-if="rows.length === 0">
                        <td colspan="6" class="p-4 text-center text-slate-500">No employees found.</td>
                    </tr>
                </tbody>
            </table>
            <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-5 py-3 text-xs text-slate-600">
                <p>Page {{ employeePagination.page }} of {{ employeePagination.last_page }} | {{ employeePagination.total }} employees</p>
                <div class="flex gap-2">
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isEmployeePrevDisabled" @click="prevEmployeePage">Prev</button>
                    <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="isEmployeeNextDisabled" @click="nextEmployeePage">Next</button>
                </div>
            </footer>
        </article>

        <p v-if="lastPasskey" class="text-sm text-emerald-700">New passkey: <strong>{{ lastPasskey }}</strong></p>

        <AppModal v-model="showDepartmentEditModal" title="Edit Department" size="md">
            <form class="grid gap-3" @submit.prevent="submitDepartmentEdit">
                <input
                    v-model="departmentEditForm.name"
                    required
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Department name"
                >
                <select v-model="departmentEditForm.head_id" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">No head assigned</option>
                    <option v-for="employee in rows" :key="employee.id" :value="employee.id">
                        {{ employee.employee_code }} - {{ employee.user?.name }}
                    </option>
                </select>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showDepartmentEditModal = false">Cancel</button>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
                </div>
            </form>
        </AppModal>

        <ConfirmModal
            v-model="showDepartmentDeleteModal"
            title="Delete Department"
            message="Are you sure you want to delete this department?"
            confirm-text="Delete Department"
            tone="danger"
            @confirm="confirmRemoveDepartment"
        />
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AppModal from '../../../components/ui/AppModal.vue';
import ConfirmModal from '../../../components/ui/ConfirmModal.vue';
import StatusBadge from '../../../components/ui/StatusBadge.vue';
import { EmployeeService } from '../../../services/employee.service';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';

const toast = useToastStore();
const search = ref('');
const rows = ref([]);
const departments = ref([]);
const employeeFilters = ref({
    page: 1,
    per_page: 20,
});
const employeePagination = ref({
    page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
});
const lastPasskey = ref('');
const showDepartmentEditModal = ref(false);
const showDepartmentDeleteModal = ref(false);
const editDepartmentId = ref(null);
const deleteDepartmentId = ref(null);
const departmentForm = ref({
    name: '',
    head_id: '',
});
const departmentEditForm = ref({
    name: '',
    head_id: '',
});

const activeCount = computed(() => rows.value.filter((row) => Boolean(row.user?.is_active)).length);
const inactiveCount = computed(() => rows.value.filter((row) => !row.user?.is_active).length);

onMounted(async () => {
    await refreshAll();
});

async function refreshAll() {
    employeeFilters.value.page = 1;
    await Promise.all([load(), loadDepartments()]);
}

async function load() {
    try {
        const response = await EmployeeService.list({
            search: search.value || undefined,
            page: employeeFilters.value.page,
            per_page: employeeFilters.value.per_page,
        });

        rows.value = response.data.data ?? [];
        syncPagination(employeePagination.value, response.data, employeeFilters.value.per_page);
        employeeFilters.value.page = employeePagination.value.page;
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load employees.'));
    }
}

async function loadDepartments() {
    try {
        const response = await EmployeeService.departments({ per_page: 200 });
        departments.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load departments.'));
    }
}

async function createDepartment() {
    try {
        await EmployeeService.createDepartment({
            name: departmentForm.value.name,
            head_id: departmentForm.value.head_id ? Number(departmentForm.value.head_id) : null,
        });

        departmentForm.value.name = '';
        departmentForm.value.head_id = '';
        toast.success('Department created successfully.');
        await loadDepartments();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create department.'));
    }
}

function openDepartmentEditModal(department) {
    editDepartmentId.value = department.id;
    departmentEditForm.value.name = department.name ?? '';
    departmentEditForm.value.head_id = department.head_id ? String(department.head_id) : '';
    showDepartmentEditModal.value = true;
}

async function submitDepartmentEdit() {
    if (!editDepartmentId.value) {
        return;
    }

    try {
        await EmployeeService.updateDepartment(editDepartmentId.value, {
            name: departmentEditForm.value.name,
            head_id: departmentEditForm.value.head_id ? Number(departmentEditForm.value.head_id) : null,
        });

        showDepartmentEditModal.value = false;
        toast.success('Department updated successfully.');
        await loadDepartments();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to update department.'));
    }
}

function openDepartmentDeleteModal(id) {
    deleteDepartmentId.value = id;
    showDepartmentDeleteModal.value = true;
}

async function confirmRemoveDepartment() {
    if (!deleteDepartmentId.value) {
        return;
    }

    try {
        await EmployeeService.removeDepartment(deleteDepartmentId.value);
        showDepartmentDeleteModal.value = false;
        deleteDepartmentId.value = null;
        toast.success('Department deleted successfully.');
        await loadDepartments();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete department.'));
    }
}

async function resetPasskey(id) {
    try {
        const response = await EmployeeService.resetPasskey(id);
        lastPasskey.value = response.data.passkey;
        toast.success('Passkey reset successfully.');
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to reset passkey.'));
    }
}

async function removeEmployee(id) {
    if (!confirm('Delete this employee record?')) {
        return;
    }

    try {
        await EmployeeService.remove(id);
        toast.success('Employee deleted successfully.');
        await Promise.all([load(), loadDepartments()]);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete employee.'));
    }
}

const isEmployeePrevDisabled = computed(() => (employeePagination.value.page ?? 1) <= 1);
const isEmployeeNextDisabled = computed(() => (employeePagination.value.page ?? 1) >= (employeePagination.value.last_page ?? 1));

async function onEmployeePerPageChange() {
    employeeFilters.value.page = 1;
    await load();
}

async function prevEmployeePage() {
    if (isEmployeePrevDisabled.value) {
        return;
    }

    employeeFilters.value.page -= 1;
    await load();
}

async function nextEmployeePage() {
    if (isEmployeeNextDisabled.value) {
        return;
    }

    employeeFilters.value.page += 1;
    await load();
}

function syncPagination(target, payload, fallbackPerPage = 20) {
    target.page = Number(payload.current_page ?? 1);
    target.per_page = Number(payload.per_page ?? fallbackPerPage);
    target.total = Number(payload.total ?? 0);
    target.last_page = Number(payload.last_page ?? 1);
}
</script>
