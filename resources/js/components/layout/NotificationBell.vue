<template>
    <div class="relative">
        <button
            type="button"
            class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
            @click="open = !open"
        >
            <span class="text-base">&#128276;</span>
            <span
                v-if="notifications.unreadCount > 0"
                class="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center"
            >
                {{ notifications.unreadCount > 99 ? '99+' : notifications.unreadCount }}
            </span>
        </button>

        <div
            v-if="open"
            class="absolute right-0 mt-2 w-88 max-w-[85vw] rounded-xl border border-slate-200 bg-white shadow-lg z-30"
        >
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-2">
                <h4 class="text-sm font-semibold text-slate-900">Notifications</h4>
                <button
                    type="button"
                    class="text-xs font-semibold text-blue-700"
                    @click="notifications.markAllRead()"
                >
                    Mark all read
                </button>
            </div>

            <ul class="max-h-80 overflow-y-auto">
                <li
                    v-for="item in notifications.notifications"
                    :key="item.id"
                    class="px-4 py-3 border-b border-slate-100 last:border-b-0"
                    :class="item.read ? 'bg-white' : 'bg-blue-50/40'"
                >
                    <button type="button" class="w-full text-left" @click="notifications.markRead(item.id)">
                        <p class="text-sm font-semibold text-slate-800">{{ titleFor(item.type) }}</p>
                        <p class="text-xs text-slate-600 mt-1">{{ subtitleFor(item) }}</p>
                    </button>
                </li>
                <li v-if="notifications.notifications.length === 0" class="px-4 py-5 text-sm text-slate-500">
                    No notifications yet.
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useNotificationStore } from '../../stores/notification.store';

const notifications = useNotificationStore();
const open = ref(false);

function titleFor(type) {
    const labels = {
        'loan.applied': 'New loan application',
        'loan.approved': 'Loan approved',
        'loan.rejected': 'Loan rejected',
        'leave.applied': 'New leave request',
        'leave.decision': 'Leave decision updated',
        'invoice.overdue': 'Invoice overdue',
        'liability.due_soon': 'Liability due soon',
        'salary.processed': 'Salary processed',
        'salary.paid': 'Salary paid',
        'message.new': 'New employee message',
        'message.replied': 'Message replied',
        'message.resolved': 'Message resolved',
        'message.action_taken': 'Message action taken',
    };

    return labels[type] ?? type;
}

function subtitleFor(item) {
    if (item.payload?.subject) {
        return item.payload.subject;
    }

    if (item.payload?.message_id) {
        return `Message ID: ${item.payload.message_id}`;
    }

    if (item.payload?.loan_id) {
        return `Loan ID: ${item.payload.loan_id}`;
    }

    if (item.payload?.invoice_number) {
        return `Invoice ${item.payload.invoice_number}`;
    }

    if (item.payload?.month) {
        return `Month: ${item.payload.month}`;
    }

    if (item.payload?.count) {
        return `${item.payload.count} item(s)`;
    }

    return 'Tap to mark as read.';
}
</script>
