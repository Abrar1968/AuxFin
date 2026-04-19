<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Employee Onboarding</p>
                <h1 class="text-2xl font-black text-slate-900">Create Employee Profile</h1>
                <p class="mt-1 text-sm text-slate-600">Create login credentials, salary defaults, and payroll policy values in one flow.</p>
            </div>
            <button
                type="button"
                class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                @click="router.push({ name: 'admin.employees' })"
            >
                Back to Employees
            </button>
        </header>

        <AppAlert v-if="errorMessage" type="error" :message="errorMessage" />

        <form class="space-y-5" @submit.prevent="submit">
            <AppCard elevated>
                <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Identity</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <AppInput v-model="form.name" label="Full Name" placeholder="e.g. Irfan Ali" />
                        <p v-if="fieldErrors.name" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.name }}</p>
                    </div>

                    <div>
                        <AppInput v-model="form.email" type="email" label="Work Email" placeholder="name@company.com" />
                        <p v-if="fieldErrors.email" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.email }}</p>
                    </div>

                    <div>
                        <AppInput v-model="form.designation" label="Designation" placeholder="Senior Accountant" />
                        <p v-if="fieldErrors.designation" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.designation }}</p>
                    </div>

                    <div>
                        <AppInput v-model="form.date_of_joining" type="date" label="Date of Joining" />
                        <p v-if="fieldErrors.date_of_joining" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.date_of_joining }}</p>
                    </div>

                    <div>
                        <AppSelect v-model="form.role" label="Role" :options="USER_ROLE_OPTIONS" />
                    </div>

                    <div>
                        <AppSelect
                            v-model="form.department_id"
                            label="Department"
                            :options="departmentOptions"
                            :disabled="departmentsLoading"
                        />
                        <p v-if="fieldErrors.department_id" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.department_id }}</p>
                    </div>
                </div>
            </AppCard>

            <AppCard elevated>
                <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Compensation</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <AppInput v-model="form.basic_salary" type="number" label="Basic Salary" placeholder="50000" />
                        <p v-if="fieldErrors.basic_salary" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.basic_salary }}</p>
                    </div>

                    <div>
                        <AppInput v-model="form.house_rent" type="number" label="House Rent" placeholder="0" />
                        <p v-if="fieldErrors.house_rent" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.house_rent }}</p>
                    </div>

                    <div>
                        <AppInput v-model="form.conveyance" type="number" label="Conveyance" placeholder="0" />
                        <p v-if="fieldErrors.conveyance" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.conveyance }}</p>
                    </div>

                    <div>
                        <AppInput v-model="form.medical_allowance" type="number" label="Medical Allowance" placeholder="0" />
                        <p v-if="fieldErrors.medical_allowance" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.medical_allowance }}</p>
                    </div>

                    <div>
                        <AppInput v-model="form.pf_rate" type="number" label="PF Rate (%)" placeholder="0" />
                        <p v-if="fieldErrors.pf_rate" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.pf_rate }}</p>
                    </div>

                    <div>
                        <AppInput v-model="form.tds_rate" type="number" label="TDS Rate (%)" placeholder="0" />
                        <p v-if="fieldErrors.tds_rate" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.tds_rate }}</p>
                    </div>

                    <div>
                        <AppInput v-model="form.professional_tax" type="number" label="Professional Tax" placeholder="0" />
                        <p v-if="fieldErrors.professional_tax" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.professional_tax }}</p>
                    </div>
                </div>
            </AppCard>

            <AppCard elevated>
                <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Banking & Policy</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <AppInput v-model="form.bank_name" label="Bank Name" placeholder="ABC Bank" />
                    </div>

                    <div class="lg:col-span-2">
                        <AppInput v-model="form.bank_account_number" label="Bank Account Number" placeholder="001122334455" />
                    </div>

                    <div>
                        <AppInput v-model="form.late_threshold_days" type="number" label="Late Threshold Days" placeholder="3" />
                        <p v-if="fieldErrors.late_threshold_days" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.late_threshold_days }}</p>
                    </div>

                    <div>
                        <AppSelect v-model="form.late_penalty_type" label="Late Penalty Type" :options="LATE_PENALTY_TYPES" />
                    </div>

                    <div>
                        <AppSelect v-model="form.working_days_per_week" label="Working Days / Week" :options="WORKING_DAYS_OPTIONS" />
                        <p v-if="fieldErrors.working_days_per_week" class="mt-1 text-xs font-semibold text-rose-600">{{ fieldErrors.working_days_per_week }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Weekly Off Days</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="day in WEEKDAY_OPTIONS"
                            :key="day"
                            type="button"
                            class="rounded-lg border px-3 py-1.5 text-xs font-semibold uppercase tracking-wide transition"
                            :class="form.weekly_off_days.includes(day)
                                ? 'border-indigo-300 bg-indigo-100 text-indigo-700'
                                : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100'"
                            @click="toggleWeeklyOff(day)"
                        >
                            {{ day.slice(0, 3) }}
                        </button>
                    </div>
                </div>
            </AppCard>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <AppButton
                    type="button"
                    variant="ghost"
                    :disabled="loading"
                    @click="router.push({ name: 'admin.employees' })"
                >
                    Cancel
                </AppButton>

                <AppButton type="submit" :loading="loading">
                    Create Employee
                </AppButton>
            </div>
        </form>

        <AppModal v-model="showPasskeyModal" title="Employee Created" size="sm">
            <div class="space-y-4">
                <AppAlert type="success" message="Employee profile was created. Share this passkey securely with the employee." />

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Temporary Passkey</p>
                    <p class="mt-1 font-mono text-2xl font-black tracking-wider text-emerald-900">{{ generatedPasskey }}</p>
                </div>
            </div>

            <template #footer>
                <div class="flex flex-wrap justify-end gap-2">
                    <AppButton variant="secondary" @click="copyPasskey">Copy Passkey</AppButton>
                    <AppButton variant="ghost" @click="showPasskeyModal = false">Close</AppButton>
                    <AppButton @click="openProfile">Open Profile</AppButton>
                </div>
            </template>
        </AppModal>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppAlert from '../../../components/ui/AppAlert.vue';
import AppButton from '../../../components/ui/AppButton.vue';
import AppCard from '../../../components/ui/AppCard.vue';
import AppInput from '../../../components/ui/AppInput.vue';
import AppModal from '../../../components/ui/AppModal.vue';
import AppSelect from '../../../components/ui/AppSelect.vue';
import { useToastStore } from '../../../stores/toast.store';
import { EmployeeService } from '../../../services/employee.service';
import { getApiErrorMessage } from '../../../utils/api-error';
import {
    LATE_PENALTY_TYPES,
    USER_ROLE_OPTIONS,
    WEEKDAY_OPTIONS,
    WORKING_DAYS_OPTIONS,
} from '../../../utils/constants';
import {
    minNumber,
    optionalIntegerRange,
    optionalMinNumber,
    required,
    toNumberOrNull,
    validEmail,
    validateFields,
} from '../../../utils/validators';

const router = useRouter();
const toast = useToastStore();

const loading = ref(false);
const errorMessage = ref('');
const fieldErrors = ref({});
const showPasskeyModal = ref(false);
const generatedPasskey = ref('');
const createdEmployeeId = ref(null);
const departments = ref([]);
const departmentsLoading = ref(false);

const departmentOptions = computed(() => [
    { label: departmentsLoading.value ? 'Loading departments...' : 'No Department', value: '' },
    ...departments.value.map((item) => ({
        label: item.name,
        value: String(item.id),
    })),
]);

const form = reactive({
    name: '',
    email: '',
    role: 'employee',
    designation: '',
    date_of_joining: '',
    department_id: '',
    bank_account_number: '',
    bank_name: '',
    basic_salary: '',
    house_rent: '',
    conveyance: '',
    medical_allowance: '',
    pf_rate: '',
    tds_rate: '',
    professional_tax: '',
    late_threshold_days: '3',
    late_penalty_type: 'half_day',
    working_days_per_week: '5',
    weekly_off_days: ['friday', 'saturday'],
});

onMounted(async () => {
    await loadDepartments();
});

async function loadDepartments() {
    departmentsLoading.value = true;

    try {
        const response = await EmployeeService.departments({ per_page: 100 });
        departments.value = response.data?.data ?? [];
    } catch {
        departments.value = [];
        toast.warning('Could not load departments. You can still create an employee without assigning one.');
    } finally {
        departmentsLoading.value = false;
    }
}

async function submit() {
    fieldErrors.value = validateFields(form, {
        name: [required('Name is required.')],
        email: [required('Email is required.'), validEmail()],
        designation: [required('Designation is required.')],
        date_of_joining: [required('Joining date is required.')],
        basic_salary: [required('Basic salary is required.'), minNumber(0)],
        department_id: [optionalIntegerRange(1, 999999)],
        house_rent: [optionalMinNumber(0)],
        conveyance: [optionalMinNumber(0)],
        medical_allowance: [optionalMinNumber(0)],
        pf_rate: [optionalMinNumber(0)],
        tds_rate: [optionalMinNumber(0)],
        professional_tax: [optionalMinNumber(0)],
        late_threshold_days: [optionalIntegerRange(1, 31)],
        working_days_per_week: [optionalIntegerRange(1, 7)],
    });

    if (Object.keys(fieldErrors.value).length > 0) {
        errorMessage.value = Object.values(fieldErrors.value)[0];
        return;
    }

    errorMessage.value = '';
    loading.value = true;

    try {
        const response = await EmployeeService.create(buildPayload());
        generatedPasskey.value = response.data?.passkey ?? '';
        createdEmployeeId.value = response.data?.employee?.id ?? null;
        showPasskeyModal.value = true;

        toast.success('Employee profile created successfully.');
        resetForm();
    } catch (error) {
        errorMessage.value = getApiErrorMessage(error, 'Unable to create employee profile.');
        toast.error(errorMessage.value);
    } finally {
        loading.value = false;
    }
}

function buildPayload() {
    return {
        name: form.name.trim(),
        email: form.email.trim(),
        role: form.role,
        designation: form.designation.trim(),
        date_of_joining: form.date_of_joining,
        department_id: toNumberOrNull(form.department_id),
        bank_account_number: form.bank_account_number.trim() || null,
        bank_name: form.bank_name.trim() || null,
        basic_salary: Number(form.basic_salary),
        house_rent: toNumberOrNull(form.house_rent),
        conveyance: toNumberOrNull(form.conveyance),
        medical_allowance: toNumberOrNull(form.medical_allowance),
        pf_rate: toNumberOrNull(form.pf_rate),
        tds_rate: toNumberOrNull(form.tds_rate),
        professional_tax: toNumberOrNull(form.professional_tax),
        late_threshold_days: toNumberOrNull(form.late_threshold_days),
        late_penalty_type: form.late_penalty_type || null,
        working_days_per_week: toNumberOrNull(form.working_days_per_week),
        weekly_off_days: [...form.weekly_off_days],
    };
}

function resetForm() {
    form.name = '';
    form.email = '';
    form.role = 'employee';
    form.designation = '';
    form.date_of_joining = '';
    form.department_id = '';
    form.bank_account_number = '';
    form.bank_name = '';
    form.basic_salary = '';
    form.house_rent = '';
    form.conveyance = '';
    form.medical_allowance = '';
    form.pf_rate = '';
    form.tds_rate = '';
    form.professional_tax = '';
    form.late_threshold_days = '3';
    form.late_penalty_type = 'half_day';
    form.working_days_per_week = '5';
    form.weekly_off_days = ['friday', 'saturday'];
}

function toggleWeeklyOff(day) {
    if (form.weekly_off_days.includes(day)) {
        form.weekly_off_days = form.weekly_off_days.filter((item) => item !== day);
        return;
    }

    form.weekly_off_days = [...form.weekly_off_days, day];
}

async function copyPasskey() {
    if (!generatedPasskey.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(generatedPasskey.value);
        toast.success('Passkey copied to clipboard.');
    } catch {
        toast.warning('Could not copy automatically. Please copy manually.');
    }
}

function openProfile() {
    if (!createdEmployeeId.value) {
        showPasskeyModal.value = false;
        return;
    }

    showPasskeyModal.value = false;
    router.push({
        name: 'admin.employee.detail',
        params: { id: createdEmployeeId.value },
    });
}
</script>
