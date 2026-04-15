<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Create Message</h3>
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

        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Status</label>
                <select v-model="status" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">All</option>
                    <option value="open">Open</option>
                    <option value="under_review">Under Review</option>
                    <option value="resolved">Resolved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Type</label>
                <select v-model="type" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
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
                <label class="text-xs font-semibold text-slate-600">Employee ID</label>
                <input v-model.number="employeeId" type="number" min="1" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2" placeholder="Optional">
            </div>

            <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold" @click="load">Refresh</button>
            <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="markAllRead">Mark all read</button>

            <span class="text-xs font-semibold text-slate-600">Unread: {{ unreadCount }}</span>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
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
                    <tr v-for="msg in rows" :key="msg.id" class="border-t border-slate-100" :class="selectedId === msg.id ? 'bg-blue-50/50' : ''">
                        <td class="p-3">
                            <span
                                v-if="!msg.is_read"
                                class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-600"
                            ></span>
                        </td>
                        <td class="p-3">{{ msg.employee?.user?.name }}</td>
                        <td class="p-3">{{ msg.type }}</td>
                        <td class="p-3">{{ msg.subject }}</td>
                        <td class="p-3">{{ msg.priority }}</td>
                        <td class="p-3">{{ msg.status }}</td>
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

        <article v-if="selected" class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-900">{{ selected.subject }}</h3>
                    <p class="text-xs text-slate-600 mt-1">{{ selected.type }} | {{ selected.status }}</p>
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
    const echo = window.EchoChat || window.EchoNotifications || window.Echo || window.EchoMain;
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
</script>
