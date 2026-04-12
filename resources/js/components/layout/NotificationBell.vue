<template>
    <div class="relative">
        <button
            type="button"
            class="fin-focus-ring relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-sky-200/80 bg-white/85 text-slate-700 shadow-sm transition hover:-translate-y-px hover:bg-white"
            @click="open = !open"
        >
            <span class="text-base">&#128276;</span>
            <span
                v-if="notifications.unreadCount > 0"
                class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-slate-900 shadow-[0_6px_12px_rgba(245,158,11,.45)]"
            >
                {{ notifications.unreadCount > 99 ? '99+' : notifications.unreadCount }}
            </span>
        </button>

        <div
            v-if="open"
            class="fin-glass absolute right-0 z-30 mt-2 w-88 max-w-[85vw] rounded-xl border border-sky-100/80 shadow-[0_20px_44px_rgba(2,132,199,.16)]"
        >
            <div class="flex items-center justify-between gap-2 border-b border-sky-100 px-4 py-3">
                <h4 class="text-sm font-semibold text-slate-900">Notifications</h4>
                <button
                    type="button"
                    class="text-xs font-semibold text-sky-700 hover:text-sky-900"
                    @click="notifications.markAllRead()"
                >
                    Mark all read
                </button>
            </div>

            <ul class="max-h-80 overflow-y-auto">
                <li
                    v-for="item in notifications.notifications"
                    :key="item.id"
                    class="border-b border-sky-100/70 px-4 py-3 last:border-b-0"
                    :class="item.read ? 'bg-transparent' : 'bg-sky-50/60'"
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
        'insight.analytics.overview': 'Insights: Overview refreshed',
        'insight.analytics.cmgr': 'Insights: CMGR recalculated',
        'insight.analytics.growth': 'Insights: Growth metrics updated',
        'insight.analytics.forecast': 'Insights: Forecast updated',
        'insight.analytics.runway': 'Insights: Runway updated',
        'insight.analytics.anomalies': 'Insights: Anomaly scan complete',
        'insight.analytics.ar_health': 'Insights: AR health updated',
        'insight.report.profit_loss': 'Insights: Profit & Loss generated',
        'insight.report.tax_summary': 'Insights: Tax summary generated',
        'insight.report.ar_aging': 'Insights: AR aging generated',
        'insight.security.audit': 'Insights: Security audit completed',
    };

    return labels[type] ?? type;
}

function subtitleFor(item) {
    if (String(item.type).startsWith('insight.') && item.payload?.scope) {
        if (item.payload?.generated_at) {
            const time = new Date(item.payload.generated_at).toLocaleTimeString();
            return `${item.payload.scope} stream at ${time}`;
        }

        return `${item.payload.scope} stream updated.`;
    }

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
