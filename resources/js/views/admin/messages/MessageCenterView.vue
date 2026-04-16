<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Service Desk</p>
                <h1 class="text-2xl font-black text-slate-900">Employee Message Center</h1>
                <p class="mt-1 text-sm text-slate-600">Handle inquiries, resolve appeals, and maintain response SLAs with structured actions.</p>
            </div>

            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-right">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-700">Unread Inbox</p>
                <p class="mt-1 text-2xl font-black text-indigo-900">{{ unreadCount }}</p>
            </div>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Total Messages</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ rows.length }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Open</p>
                <p class="mt-2 text-2xl font-black text-amber-800">{{ openCount }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-700">Under Review</p>
                <p class="mt-2 text-2xl font-black text-indigo-900">{{ underReviewCount }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Resolved</p>
                <p class="mt-2 text-2xl font-black text-emerald-800">{{ resolvedCount }}</p>
            </article>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Compose Message</h2>
            <form class="mt-3 grid md:grid-cols-4 gap-3" @submit.prevent="createMessage">
                <select v-model="compose.employee_id" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select employee</option>
                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                        {{ employee.employee_code }} - {{ employee.user?.name }}
                    </option>
                </select>
                <select v-model="compose.type" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="general_hr">General HR</option>
                    <option value="salary_query">Salary Query</option>
                    <option value="loan_query">Loan Query</option>
                    <option value="leave_clarification">Leave Clarification</option>
                    <option value="late_appeal">Late Appeal</option>
                    <option value="deduction_dispute">Deduction Dispute</option>
                </select>
                <input v-model="compose.subject" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Subject">
                <select v-model="compose.priority" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="normal">Normal</option>
                    <option value="high">High</option>
                </select>
                <textarea
                    v-model="compose.body"
                    required
                    rows="2"
                    class="md:col-span-4 rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Message body"
                ></textarea>
                <button class="md:col-span-4 rounded-lg bg-emerald-600 text-white px-4 py-2 text-sm font-semibold">Send Message</button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Status</label>
                <select v-model="status" class="mt-1 block rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">All</option>
                    <option value="open">Open</option>
                    <option value="under_review">Under Review</option>
                    <option value="resolved">Resolved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Type</label>
                <select v-model="type" class="mt-1 block rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">All</option>
                    <option value="late_appeal">Late Appeal</option>
                    <option value="deduction_dispute">Deduction Dispute</option>
                    <option value="leave_clarification">Leave Clarification</option>
                    <option value="salary_query">Salary Query</option>
                    <option value="loan_query">Loan Query</option>
                    <option value="general_hr">General HR</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Employee ID</label>
                <input v-model.number="employeeId" type="number" min="1" class="mt-1 block rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Optional">
            </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700" @click="load">Refresh</button>
                    <button class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="markAllRead">Mark all read</button>
                </div>
            </div>
        </article>

        <article class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Conversation Queue</h3>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="text-left p-3 w-10"></th>
                        <th class="text-left p-3">Employee</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Subject</th>
                        <th class="text-left p-3">Priority</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Updated</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="msg in rows" :key="msg.id" class="border-t border-slate-100 hover:bg-slate-50/70" :class="selectedId === msg.id ? 'bg-blue-50/50' : ''">
                        <td class="p-3">
                            <span
                                v-if="!msg.is_read"
                                class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-600"
                            ></span>
                        </td>
                        <td class="p-3">{{ msg.employee?.user?.name }}</td>
                        <td class="p-3 capitalize">{{ msg.type?.replaceAll('_', ' ') }}</td>
                        <td class="p-3">{{ msg.subject }}</td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="priorityClass(msg.priority)">
                                {{ msg.priority }}
                            </span>
                        </td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="statusClass(msg.status)">
                                {{ msg.status?.replaceAll('_', ' ') }}
                            </span>
                        </td>
                        <td class="p-3">{{ formatDateTime(msg.updated_at) }}</td>
                        <td class="p-3 text-right">
                            <button class="text-xs font-semibold text-blue-700" @click="openMessage(msg.id)">Open</button>
                            <button class="ml-3 text-xs font-semibold text-rose-700" @click="openDeleteMessageModal(msg.id)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td class="p-3 text-slate-500" colspan="8">No messages found.</td>
                    </tr>
                </tbody>
            </table>
        </article>

        <article v-if="selected" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-900">{{ selected.subject }}</h3>
                    <p class="mt-1 text-xs text-slate-600">{{ selected.type }} | {{ selected.status }}</p>
                </div>
                <div class="text-xs text-slate-600 text-right">
                    <p>Employee: {{ selected.employee?.user?.name }}</p>
                    <p v-if="selected.reference_date">Date Ref: {{ selected.reference_date }}</p>
                    <p v-if="selected.reference_month">Month Ref: {{ selected.reference_month }}</p>
                </div>
            </div>

            <p class="text-sm text-slate-800 whitespace-pre-wrap">{{ selected.body }}</p>

            <div v-if="selected.admin_reply" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm">
                <p class="text-xs font-semibold text-emerald-700">Latest Admin Reply</p>
                <p class="mt-1 text-emerald-900 whitespace-pre-wrap">{{ selected.admin_reply }}</p>
            </div>

            <div class="grid md:grid-cols-3 gap-3">
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Reply</label>
                    <textarea
                        v-model="reply.admin_reply"
                        rows="4"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                        placeholder="Write response for this message"
                    ></textarea>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Status</label>
                        <select v-model="reply.status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="under_review">Under Review</option>
                            <option value="resolved">Resolved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Action Taken</label>
                        <select v-model="reply.action_taken" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" :disabled="!canTakeAction">
                            <option value="none">None</option>
                            <option value="noted">Noted</option>
                            <option value="mark_excused">Mark Excused</option>
                            <option value="deduction_reversed">Deduction Reversed</option>
                            <option value="salary_adjusted">Salary Adjusted</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="submitReply">Save Reply</button>
                <button class="rounded-lg border border-emerald-300 text-emerald-700 px-4 py-2 text-sm font-semibold" @click="resolveSelected">Mark Resolved</button>
                <button class="rounded-lg border border-rose-300 text-rose-700 px-4 py-2 text-sm font-semibold" @click="openRejectModal">Reject</button>
            </div>
        </article>

        <AppModal v-model="showRejectModal" title="Reject Message" size="sm">
            <form class="grid gap-3" @submit.prevent="submitRejectMessage">
                <textarea
                    v-model="rejectReason"
                    required
                    rows="3"
                    class="rounded-lg border border-slate-300 px-3 py-2"
                    placeholder="Enter rejection reason"
                ></textarea>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showRejectModal = false">Cancel</button>
                    <button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Reject</button>
                </div>
            </form>
        </AppModal>

        <ConfirmModal
            v-model="showDeleteModal"
            title="Delete Message"
            message="Are you sure you want to delete this message?"
            confirm-text="Delete Message"
            tone="danger"
            @confirm="confirmDeleteMessage"
        />
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import AppModal from '../../../components/ui/AppModal.vue';
import ConfirmModal from '../../../components/ui/ConfirmModal.vue';
import { EmployeeService } from '../../../services/employee.service';
import { MessageService } from '../../../services/message.service';
import { useAuthStore } from '../../../stores/auth.store';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';

const auth = useAuthStore();
const toast = useToastStore();

const status = ref('');
const type = ref('');
const employeeId = ref(null);
const employees = ref([]);
const rows = ref([]);
const selectedId = ref(null);
const selected = ref(null);
const unreadCount = ref(0);
const showRejectModal = ref(false);
const showDeleteModal = ref(false);
const rejectReason = ref('');
const deleteMessageId = ref(null);

const reply = reactive({
    admin_reply: '',
    status: 'under_review',
    action_taken: 'none',
});

const compose = reactive({
    employee_id: '',
    type: 'general_hr',
    subject: '',
    body: '',
    priority: 'normal',
});

let adminChannel = null;

const canTakeAction = computed(() => ['late_appeal', 'deduction_dispute'].includes(selected.value?.type ?? ''));
const openCount = computed(() => rows.value.filter((msg) => String(msg.status).toLowerCase() === 'open').length);
const underReviewCount = computed(() => rows.value.filter((msg) => String(msg.status).toLowerCase() === 'under_review').length);
const resolvedCount = computed(() => rows.value.filter((msg) => String(msg.status).toLowerCase() === 'resolved').length);

onMounted(async () => {
    await loadEmployees();
    await load();
    subscribeRealTime();
});

onUnmounted(() => {
    if (adminChannel) {
        adminChannel.stopListening('.message.new');
        adminChannel.stopListening('message.new');
    }
});

async function loadEmployees() {
    try {
        const response = await EmployeeService.list({ per_page: 200 });
        employees.value = response.data.data ?? [];
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load employees.'));
    }
}

async function load() {
    try {
        const response = await MessageService.adminList({
            status: status.value || undefined,
            type: type.value || undefined,
            employee_id: employeeId.value || undefined,
        });

        rows.value = response.data.data ?? [];
        unreadCount.value = Number(response.data.unread_count ?? 0);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load messages.'));
    }
}

async function createMessage() {
    try {
        await MessageService.adminCreate({
            employee_id: Number(compose.employee_id),
            type: compose.type,
            subject: compose.subject,
            body: compose.body,
            priority: compose.priority,
        });

        compose.employee_id = '';
        compose.type = 'general_hr';
        compose.subject = '';
        compose.body = '';
        compose.priority = 'normal';

        toast.success('Message created successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create message.'));
    }
}

async function openMessage(id) {
    try {
        const response = await MessageService.adminShow(id);
        selected.value = response.data;
        selectedId.value = response.data.id;
        reply.admin_reply = response.data.admin_reply ?? '';
        reply.status = response.data.status === 'open' ? 'under_review' : (response.data.status ?? 'under_review');
        reply.action_taken = response.data.action_taken ?? 'none';
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load message details.'));
    }
}

async function submitReply() {
    if (!selected.value) {
        return;
    }

    if (!reply.admin_reply || reply.admin_reply.trim().length < 3) {
        toast.warning('Reply should be at least 3 characters.');
        return;
    }

    try {
        await MessageService.adminReply(selected.value.id, {
            admin_reply: reply.admin_reply,
            status: reply.status,
            action_taken: canTakeAction.value ? reply.action_taken : 'none',
        });

        toast.success('Reply saved successfully.');
        await openMessage(selected.value.id);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to save reply.'));
    }
}

async function resolveSelected() {
    if (!selected.value) {
        return;
    }

    try {
        await MessageService.adminResolve(selected.value.id);
        toast.success('Message marked as resolved.');
        await openMessage(selected.value.id);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to resolve message.'));
    }
}

function openRejectModal() {
    if (!selected.value) {
        return;
    }

    rejectReason.value = '';
    showRejectModal.value = true;
}

async function submitRejectMessage() {
    if (!selected.value || !rejectReason.value.trim()) {
        return;
    }

    try {
        await MessageService.adminReject(selected.value.id, { reason: rejectReason.value });
        showRejectModal.value = false;
        toast.success('Message rejected successfully.');
        await openMessage(selected.value.id);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to reject message.'));
    }
}

function openDeleteMessageModal(id) {
    deleteMessageId.value = id;
    showDeleteModal.value = true;
}

async function confirmDeleteMessage() {
    if (!deleteMessageId.value) {
        return;
    }

    try {
        await MessageService.adminDelete(deleteMessageId.value);
        showDeleteModal.value = false;
        toast.success('Message deleted successfully.');

        if (selectedId.value === deleteMessageId.value) {
            selectedId.value = null;
            selected.value = null;
            reply.admin_reply = '';
            reply.status = 'under_review';
            reply.action_taken = 'none';
        }

        deleteMessageId.value = null;

        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete message.'));
    }
}

async function markAllRead() {
    try {
        await MessageService.adminMarkAllRead();
        toast.success('All messages marked as read.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to mark all as read.'));
    }
}

function subscribeRealTime() {
    const echo = window.EchoMain || window.EchoChat || window.EchoNotifications || window.Echo;
    if (!echo || !auth.token) {
        return;
    }

    if (typeof window.configureEchoAuth === 'function') {
        window.configureEchoAuth(auth.token);
    }

    adminChannel = echo.private('admin-broadcast');

    adminChannel.listen('.message.new', async () => {
        toast.info('New employee message received.');
        await load();
    });

    adminChannel.listen('message.new', async () => {
        toast.info('New employee message received.');
        await load();
    });
}

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
}

function statusClass(status) {
    const value = String(status ?? '').toLowerCase();

    if (value === 'open') {
        return 'bg-amber-100 text-amber-700';
    }

    if (value === 'under_review') {
        return 'bg-indigo-100 text-indigo-700';
    }

    if (value === 'resolved') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (value === 'rejected') {
        return 'bg-rose-100 text-rose-700';
    }

    return 'bg-slate-100 text-slate-700';
}

function priorityClass(priority) {
    const value = String(priority ?? '').toLowerCase();

    if (value === 'high') {
        return 'bg-rose-100 text-rose-700';
    }

    return 'bg-slate-100 text-slate-700';
}
</script>
