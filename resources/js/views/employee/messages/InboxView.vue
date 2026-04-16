<template>
    <section class="space-y-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold">Compose Query</h3>
            <form class="mt-3 grid md:grid-cols-2 gap-3" @submit.prevent="create">
                <select v-model="form.type" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select Type</option>
                    <option value="late_appeal">Late Appeal</option>
                    <option value="deduction_dispute">Deduction Dispute</option>
                    <option value="leave_clarification">Leave Clarification</option>
                    <option value="salary_query">Salary Query</option>
                    <option value="loan_query">Loan Query</option>
                    <option value="general_hr">General HR</option>
                </select>
                <input v-model="form.subject" required class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Subject">
                <textarea v-model="form.body" required rows="3" class="md:col-span-2 rounded-lg border border-slate-300 px-3 py-2" placeholder="Message"></textarea>

                <input v-model="form.reference_date" type="date" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Reference date">
                <input v-model="form.reference_month" type="date" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Reference month">

                <select v-model="form.priority" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="normal">Normal Priority</option>
                    <option value="high">High Priority</option>
                </select>

                <button class="md:col-span-2 rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Submit</button>
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
                <select v-model="filterType" class="block mt-1 rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">All</option>
                    <option value="late_appeal">Late Appeal</option>
                    <option value="deduction_dispute">Deduction Dispute</option>
                    <option value="leave_clarification">Leave Clarification</option>
                    <option value="salary_query">Salary Query</option>
                    <option value="loan_query">Loan Query</option>
                    <option value="general_hr">General HR</option>
                </select>
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
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Subject</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Updated</th>
                        <th class="text-left p-3">Reply</th>
                        <th class="text-right p-3">Action</th>
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
                        <td class="p-3">{{ msg.type }}</td>
                        <td class="p-3">{{ msg.subject }}</td>
                        <td class="p-3">{{ msg.status }}</td>
                        <td class="p-3">{{ formatDateTime(msg.updated_at) }}</td>
                        <td class="p-3">{{ msg.admin_reply ?? '-' }}</td>
                        <td class="p-3 text-right">
                            <button class="text-xs font-semibold text-blue-700" @click="openMessage(msg.id)">View</button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td class="p-3 text-slate-500" colspan="7">No messages found.</td>
                    </tr>
                </tbody>
            </table>
        </article>

        <article v-if="selected" class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
            <h3 class="font-bold">{{ selected.subject }}</h3>
            <p class="text-xs text-slate-600">{{ selected.type }} | {{ selected.status }}</p>
            <p class="text-sm text-slate-800 whitespace-pre-wrap">{{ selected.body }}</p>

            <div v-if="selected.admin_reply" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm">
                <p class="text-xs font-semibold text-emerald-700">Admin Reply</p>
                <p class="mt-1 text-emerald-900 whitespace-pre-wrap">{{ selected.admin_reply }}</p>
            </div>

            <p class="text-xs text-slate-500">Action Taken: {{ selected.action_taken ?? 'none' }}</p>
        </article>
    </section>
</template>

<script setup>
import { onMounted, onUnmounted, reactive, ref } from 'vue';
import { MessageService } from '../../../services/message.service';
import { useAuthStore } from '../../../stores/auth.store';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';

const auth = useAuthStore();
const toast = useToastStore();

const status = ref('');
const filterType = ref('');
const rows = ref([]);
const unreadCount = ref(0);
const selectedId = ref(null);
const selected = ref(null);

const form = reactive({
    type: '',
    subject: '',
    body: '',
    reference_date: '',
    reference_month: '',
    priority: 'normal',
});

let employeeChannel = null;

onMounted(async () => {
    await load();
    subscribeRealTime();
});

onUnmounted(() => {
    if (employeeChannel) {
        employeeChannel.stopListening('.message.replied');
        employeeChannel.stopListening('message.replied');
        employeeChannel.stopListening('.message.resolved');
        employeeChannel.stopListening('message.resolved');
        employeeChannel.stopListening('.message.action_taken');
        employeeChannel.stopListening('message.action_taken');
    }
});

async function load() {
    try {
        const response = await MessageService.inbox({
            status: status.value || undefined,
            type: filterType.value || undefined,
        });

        rows.value = response.data.data ?? [];
        unreadCount.value = Number(response.data.unread_count ?? 0);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load inbox.'));
    }
}

async function create() {
    try {
        await MessageService.create({
            type: form.type,
            subject: form.subject,
            body: form.body,
            reference_date: form.reference_date || null,
            reference_month: form.reference_month || null,
            priority: form.priority,
        });

        form.type = '';
        form.subject = '';
        form.body = '';
        form.reference_date = '';
        form.reference_month = '';
        form.priority = 'normal';

        toast.success('Message submitted successfully.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to submit message.'));
    }
}

async function openMessage(id) {
    try {
        const response = await MessageService.inboxShow(id);
        selected.value = response.data;
        selectedId.value = response.data.id;
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load message details.'));
    }
}

async function markAllRead() {
    try {
        await MessageService.inboxMarkAllRead();
        toast.success('Inbox marked as read.');
        await load();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to mark inbox as read.'));
    }
}

function subscribeRealTime() {
    const employeeId = auth.user?.employee?.id;
    const echo = window.EchoMain || window.EchoChat || window.EchoNotifications || window.Echo;

    if (!echo || !auth.token || !employeeId) {
        return;
    }

    if (typeof window.configureEchoAuth === 'function') {
        window.configureEchoAuth(auth.token);
    }

    employeeChannel = echo.private(`employee.${employeeId}`);

    employeeChannel.listen('.message.replied', async () => {
        toast.info('Admin replied to one of your messages.');
        await load();
    });

    employeeChannel.listen('message.replied', async () => {
        toast.info('Admin replied to one of your messages.');
        await load();
    });

    employeeChannel.listen('.message.resolved', async () => {
        toast.success('A message has been resolved.');
        await load();
    });

    employeeChannel.listen('message.resolved', async () => {
        toast.success('A message has been resolved.');
        await load();
    });

    employeeChannel.listen('.message.action_taken', async () => {
        toast.info('An action was taken on your message.');
        await load();
    });

    employeeChannel.listen('message.action_taken', async () => {
        toast.info('An action was taken on your message.');
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
