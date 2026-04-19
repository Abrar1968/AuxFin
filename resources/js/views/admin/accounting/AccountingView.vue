<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Accounting Center</p>
                <h1 class="text-2xl font-black text-slate-900">Accrual Accounting Statements</h1>
                <p class="mt-1 text-sm text-slate-600">Unified trial balance, balance sheet, cash flow, general ledger, and payment ledger.</p>
            </div>

            <button
                class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                @click="loadAll"
            >
                Refresh Accounting
            </button>
        </header>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Statement Controls</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">As Of</label>
                    <input v-model="filters.as_of" type="date" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">From Month</label>
                    <input v-model="filters.from_month" type="date" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">To Month</label>
                    <input v-model="filters.to_month" type="date" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </article>

        <div v-if="loading" class="grid gap-4">
            <SkeletonLoader height="18" />
            <SkeletonLoader height="18" />
            <SkeletonLoader height="18" />
            <SkeletonLoader height="18" />
        </div>

        <template v-else>
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Owner Equity Ledger</h2>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Per page</span>
                        <select v-model.number="ownerEquityFilters.per_page" class="rounded-lg border border-slate-300 px-2 py-1" @change="onOwnerEquityPerPageChange">
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                        </select>
                    </div>
                </div>

                <form class="mt-3 grid gap-3 md:grid-cols-5" @submit.prevent="createOwnerEquityEntry">
                    <input v-model="ownerEquityForm.entry_date" type="date" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <select v-model="ownerEquityForm.entry_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="capital_contribution">Capital Contribution</option>
                        <option value="drawing">Drawing</option>
                    </select>
                    <input v-model="ownerEquityForm.amount" type="number" min="0.01" step="0.01" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Amount">
                    <input v-model="ownerEquityForm.notes" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Notes (optional)">
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Add Entry</button>
                </form>

                <table class="mt-3 w-full text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="p-3 text-left">Date</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-left">Amount</th>
                            <th class="p-3 text-left">Notes</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in ownerEquity.rows" :key="entry.id" class="border-t border-slate-100">
                            <td class="p-3">{{ entry.entry_date }}</td>
                            <td class="p-3">{{ String(entry.entry_type ?? '').replaceAll('_', ' ') }}</td>
                            <td class="p-3">{{ number(entry.amount) }}</td>
                            <td class="p-3">{{ entry.notes ?? '-' }}</td>
                            <td class="space-x-3 p-3 text-right">
                                <button class="text-xs font-semibold text-amber-700" @click="openOwnerEquityEditModal(entry)">Edit</button>
                                <button class="text-xs font-semibold text-rose-700" @click="removeOwnerEquityEntry(entry.id)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="ownerEquity.rows.length === 0">
                            <td colspan="5" class="p-4 text-center text-slate-500">No owner equity entries found.</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                    <p>Page {{ ownerEquity.pagination.page }} of {{ ownerEquity.pagination.last_page }} | {{ ownerEquity.pagination.total }} entries</p>
                    <div class="flex gap-2">
                        <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="(ownerEquity.pagination.page ?? 1) <= 1" @click="prevOwnerEquityPage">Prev</button>
                        <button class="rounded-lg border border-slate-300 px-3 py-1 font-semibold text-slate-700 disabled:opacity-50" :disabled="(ownerEquity.pagination.page ?? 1) >= (ownerEquity.pagination.last_page ?? 1)" @click="nextOwnerEquityPage">Next</button>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Balance Sheet Snapshot</h2>
                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold" @click="downloadBalanceSheetPdf">PDF</button>
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold" @click="downloadBalanceSheetCsv">CSV</button>
                    </div>
                </div>

                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Assets</p>
                        <p class="mt-2 text-lg font-black text-slate-900">{{ number(balanceSheet.totals?.assets) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Liabilities</p>
                        <p class="mt-2 text-lg font-black text-slate-900">{{ number(balanceSheet.totals?.liabilities) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Equity</p>
                        <p class="mt-2 text-lg font-black text-slate-900">{{ number(balanceSheet.totals?.equity) }}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Balanced</p>
                        <p class="mt-2 text-lg font-black" :class="balanceSheet.is_balanced ? 'text-emerald-800' : 'text-rose-700'">
                            {{ balanceSheet.is_balanced ? 'Yes' : 'No' }}
                        </p>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Trial Balance</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs text-slate-600">As of {{ trialBalance.as_of ?? '-' }}</span>
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold" @click="downloadTrialBalancePdf">PDF</button>
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold" @click="downloadTrialBalanceCsv">CSV</button>
                    </div>
                </div>

                <table class="mt-3 w-full text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="p-3 text-left">Code</th>
                            <th class="p-3 text-left">Account</th>
                            <th class="p-3 text-left">Debit</th>
                            <th class="p-3 text-left">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in trialBalance.lines ?? []" :key="line.account_code" class="border-t border-slate-100">
                            <td class="p-3">{{ line.account_code }}</td>
                            <td class="p-3">{{ line.account_name }}</td>
                            <td class="p-3">{{ number(line.debit) }}</td>
                            <td class="p-3">{{ number(line.credit) }}</td>
                        </tr>
                        <tr class="border-t-2 border-slate-200 bg-slate-50 font-semibold">
                            <td class="p-3" colspan="2">Totals</td>
                            <td class="p-3">{{ number(trialBalance.totals?.debit) }}</td>
                            <td class="p-3">{{ number(trialBalance.totals?.credit) }}</td>
                        </tr>
                    </tbody>
                </table>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Balance Sheet Details</h2>

                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Assets</p>
                        <p class="mt-2">Cash &amp; Bank: <strong>{{ number(balanceSheet.assets?.cash_and_bank) }}</strong></p>
                        <p>Accounts Receivable: <strong>{{ number(balanceSheet.assets?.accounts_receivable) }}</strong></p>
                        <p>Prepaid Expenses: <strong>{{ number(balanceSheet.assets?.prepaid_expenses) }}</strong></p>
                        <p>Fixed Assets: <strong>{{ number(balanceSheet.assets?.fixed_assets) }}</strong></p>
                        <p>Fixed Assets (Gross): <strong>{{ number(balanceSheet.assets?.fixed_assets_gross) }}</strong></p>
                        <p>Accumulated Depreciation: <strong>{{ number(balanceSheet.assets?.accumulated_depreciation) }}</strong></p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Liabilities</p>
                        <p class="mt-2">Outstanding: <strong>{{ number(balanceSheet.liabilities?.outstanding_liabilities) }}</strong></p>
                        <p>Accounts Payable: <strong>{{ number(balanceSheet.liabilities?.accounts_payable) }}</strong></p>
                        <p>Bank Overdraft: <strong>{{ number(balanceSheet.liabilities?.bank_overdraft) }}</strong></p>
                        <p>Unearned Revenue: <strong>{{ number(balanceSheet.liabilities?.unearned_revenue) }}</strong></p>
                        <p>Salary Payable: <strong>{{ number(balanceSheet.liabilities?.salary_payable) }}</strong></p>
                        <p>Payroll Tax Payable: <strong>{{ number(balanceSheet.liabilities?.payroll_tax_payable) }}</strong></p>
                        <p>Payroll Recoveries Reserve: <strong>{{ number(balanceSheet.liabilities?.payroll_recoveries_reserve) }}</strong></p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Equity</p>
                        <p class="mt-2">Owner Capital: <strong>{{ number(balanceSheet.equity?.owner_capital) }}</strong></p>
                        <p>Owner Drawings: <strong>{{ number(balanceSheet.equity?.owner_drawings) }}</strong></p>
                        <p class="mt-2">Retained Earnings: <strong>{{ number(balanceSheet.equity?.retained_earnings) }}</strong></p>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Cash Flow Statement</h2>
                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold" @click="downloadCashFlowPdf">PDF</button>
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold" @click="downloadCashFlowCsv">CSV</button>
                    </div>
                </div>

                <table class="mt-3 w-full text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="p-3 text-left">Month</th>
                            <th class="p-3 text-left">Collections</th>
                            <th class="p-3 text-left">Operating Outflow</th>
                            <th class="p-3 text-left">Financing Outflow</th>
                            <th class="p-3 text-left">Net Cash Flow</th>
                            <th class="p-3 text-left">Closing Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in cashFlow.rows ?? []" :key="row.month" class="border-t border-slate-100">
                            <td class="p-3">{{ row.month }}</td>
                            <td class="p-3">{{ number(row.cash_in_collections) }}</td>
                            <td class="p-3">{{ number(row.operating_outflow) }}</td>
                            <td class="p-3">{{ number(row.financing_outflow) }}</td>
                            <td class="p-3">{{ number(row.net_cash_flow) }}</td>
                            <td class="p-3">{{ number(row.closing_balance) }}</td>
                        </tr>
                    </tbody>
                </table>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">General Ledger (Accrual Journal)</h2>
                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold" @click="downloadGeneralLedgerPdf">PDF</button>
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold" @click="downloadGeneralLedgerCsv">CSV</button>
                    </div>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-6">
                    <input
                        v-model="generalLedgerFilters.project_id"
                        type="number"
                        min="1"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Project ID"
                    >
                    <input
                        v-model="generalLedgerFilters.invoice_id"
                        type="number"
                        min="1"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Invoice ID"
                    >
                    <select v-model.number="generalLedgerFilters.per_page" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option :value="25">25 / page</option>
                        <option :value="50">50 / page</option>
                        <option :value="100">100 / page</option>
                        <option :value="200">200 / page</option>
                    </select>
                    <button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold" @click="applyGeneralLedgerFilters">Apply</button>
                    <button
                        class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold"
                        :disabled="(generalLedger.pagination?.page ?? 1) <= 1"
                        @click="prevGeneralLedgerPage"
                    >
                        Prev Page
                    </button>
                    <button
                        class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold"
                        :disabled="(generalLedger.pagination?.page ?? 1) >= (generalLedger.pagination?.last_page ?? 1)"
                        @click="nextGeneralLedgerPage"
                    >
                        Next Page
                    </button>
                </div>

                <p class="mt-2 text-xs text-slate-600">
                    Page {{ generalLedger.pagination?.page ?? 1 }} of {{ generalLedger.pagination?.last_page ?? 1 }} |
                    {{ generalLedger.pagination?.total ?? 0 }} entries
                </p>

                <table class="mt-3 w-full text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="p-3 text-left">Date</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-left">Reference</th>
                            <th class="p-3 text-left">Description</th>
                            <th class="p-3 text-left">Debit</th>
                            <th class="p-3 text-left">Credit</th>
                            <th class="p-3 text-left">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in generalLedger.entries ?? []" :key="entry.reference + entry.entry_date" class="border-t border-slate-100">
                            <td class="p-3">{{ entry.entry_date }}</td>
                            <td class="p-3 capitalize">{{ String(entry.entry_type ?? '').replaceAll('_', ' ') }}</td>
                            <td class="p-3">{{ entry.reference }}</td>
                            <td class="p-3">{{ entry.description }}</td>
                            <td class="p-3">{{ entry.debit_account }}</td>
                            <td class="p-3">{{ entry.credit_account }}</td>
                            <td class="p-3">{{ number(entry.amount) }}</td>
                        </tr>
                        <tr v-if="(generalLedger.entries ?? []).length === 0">
                            <td colspan="7" class="p-4 text-center text-slate-500">No general ledger entries found in this period.</td>
                        </tr>
                    </tbody>
                </table>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Project Payment Ledger</h2>
                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold" @click="downloadPaymentLedgerPdf">PDF</button>
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold" @click="downloadPaymentLedgerCsv">CSV</button>
                    </div>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-6">
                    <input
                        v-model="paymentLedgerFilters.project_id"
                        type="number"
                        min="1"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Project ID"
                    >
                    <input
                        v-model="paymentLedgerFilters.invoice_id"
                        type="number"
                        min="1"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Invoice ID"
                    >
                    <select v-model.number="paymentLedgerFilters.per_page" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option :value="25">25 / page</option>
                        <option :value="50">50 / page</option>
                        <option :value="100">100 / page</option>
                        <option :value="200">200 / page</option>
                    </select>
                    <button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold" @click="applyPaymentLedgerFilters">Apply</button>
                    <button
                        class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold"
                        :disabled="(paymentLedger.pagination?.page ?? 1) <= 1"
                        @click="prevPaymentLedgerPage"
                    >
                        Prev Page
                    </button>
                    <button
                        class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold"
                        :disabled="(paymentLedger.pagination?.page ?? 1) >= (paymentLedger.pagination?.last_page ?? 1)"
                        @click="nextPaymentLedgerPage"
                    >
                        Next Page
                    </button>
                </div>

                <div class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Total Received</p>
                        <p class="mt-2 text-lg font-black text-slate-900">{{ number(paymentLedger.summary?.total_amount) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Payment Count</p>
                        <p class="mt-2 text-lg font-black text-slate-900">{{ paymentLedger.summary?.payment_count ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Date Range</p>
                        <p class="mt-2 text-sm font-semibold text-slate-700">{{ paymentLedger.from ?? '-' }} to {{ paymentLedger.to ?? '-' }}</p>
                    </div>
                </div>

                <p class="mt-2 text-xs text-slate-600">
                    Page {{ paymentLedger.pagination?.page ?? 1 }} of {{ paymentLedger.pagination?.last_page ?? 1 }} |
                    {{ paymentLedger.pagination?.total ?? 0 }} entries
                </p>

                <table class="mt-3 w-full text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="p-3 text-left">Date</th>
                            <th class="p-3 text-left">Project</th>
                            <th class="p-3 text-left">Invoice</th>
                            <th class="p-3 text-left">Method</th>
                            <th class="p-3 text-left">Reference</th>
                            <th class="p-3 text-left">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in paymentLedger.entries ?? []" :key="entry.payment_id" class="border-t border-slate-100">
                            <td class="p-3">{{ entry.payment_date }}</td>
                            <td class="p-3">{{ entry.project_name ?? '-' }}</td>
                            <td class="p-3">{{ entry.invoice_number ?? '-' }}</td>
                            <td class="p-3">{{ entry.payment_method ?? '-' }}</td>
                            <td class="p-3">{{ entry.reference_number ?? '-' }}</td>
                            <td class="p-3">{{ number(entry.amount) }}</td>
                        </tr>
                        <tr v-if="(paymentLedger.entries ?? []).length === 0">
                            <td colspan="6" class="p-4 text-center text-slate-500">No project payments found in this period.</td>
                        </tr>
                    </tbody>
                </table>
            </article>

            <AppModal v-model="showOwnerEquityEditModal" title="Edit Owner Equity Entry" size="md">
                <form class="grid gap-3" @submit.prevent="submitOwnerEquityEdit">
                    <input v-model="ownerEquityEditForm.entry_date" type="date" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <select v-model="ownerEquityEditForm.entry_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="capital_contribution">Capital Contribution</option>
                        <option value="drawing">Drawing</option>
                    </select>
                    <input v-model="ownerEquityEditForm.amount" type="number" min="0.01" step="0.01" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Amount">
                    <textarea v-model="ownerEquityEditForm.notes" rows="3" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Notes (optional)"></textarea>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="showOwnerEquityEditModal = false">Cancel</button>
                        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
                    </div>
                </form>
            </AppModal>
        </template>
    </section>
</template>

<script setup>
import { reactive, ref } from 'vue';
import AppModal from '../../../components/ui/AppModal.vue';
import SkeletonLoader from '../../../components/layout/SkeletonLoader.vue';
import { FinanceService } from '../../../services/finance.service';
import { ReportService } from '../../../services/report.service';
import { useToastStore } from '../../../stores/toast.store';
import { getApiErrorMessage } from '../../../utils/api-error';
import {
    exportBalanceSheetPdf,
    exportCashFlowPdf,
    exportGeneralLedgerPdf,
    exportPaymentLedgerPdf,
    exportTrialBalancePdf,
} from '../../../utils/report-pdf';

const toast = useToastStore();
const loading = ref(false);

const trialBalance = ref({ lines: [], totals: {} });
const balanceSheet = ref({ assets: {}, liabilities: {}, equity: {}, totals: {} });
const cashFlow = ref({ rows: [], totals: {} });
const generalLedger = ref({ entries: [], summary: {}, pagination: {} });
const paymentLedger = ref({ entries: [], summary: {}, pagination: {} });
const ownerEquity = ref({
    rows: [],
    pagination: {
        page: 1,
        per_page: 20,
        total: 0,
        last_page: 1,
    },
});
const ownerEquityFilters = reactive({
    page: 1,
    per_page: 20,
});
const ownerEquityForm = reactive({
    entry_date: new Date().toISOString().slice(0, 10),
    entry_type: 'capital_contribution',
    amount: '',
    notes: '',
});
const ownerEquityEditForm = reactive({
    entry_date: new Date().toISOString().slice(0, 10),
    entry_type: 'capital_contribution',
    amount: '',
    notes: '',
});
const showOwnerEquityEditModal = ref(false);
const editOwnerEquityId = ref(null);

const now = new Date();
const filters = reactive({
    as_of: now.toISOString().slice(0, 10),
    from_month: new Date(now.getFullYear(), now.getMonth() - 5, 1).toISOString().slice(0, 10),
    to_month: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10),
});

const generalLedgerFilters = reactive({
    project_id: '',
    invoice_id: '',
    per_page: 50,
    page: 1,
});

const paymentLedgerFilters = reactive({
    project_id: '',
    invoice_id: '',
    per_page: 50,
    page: 1,
});

function toMonthEndDate(value) {
    if (!value) {
        return null;
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    const monthEnd = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    return monthEnd.toISOString().slice(0, 10);
}

function optionalPositiveInt(value) {
    const parsed = Number(value);
    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}

function ledgerDateParams() {
    return {
        from_date: filters.from_month,
        to_date: toMonthEndDate(filters.to_month),
    };
}

async function loadAll() {
    loading.value = true;

    try {
        const [tb, bs, cf] = await Promise.all([
            ReportService.trialBalance({ as_of: filters.as_of }),
            ReportService.balanceSheet({ as_of: filters.as_of }),
            ReportService.cashFlow({ from_month: filters.from_month, to_month: filters.to_month }),
        ]);

        trialBalance.value = tb.data;
        balanceSheet.value = bs.data;
        cashFlow.value = cf.data;

        await Promise.all([
            loadGeneralLedger(true, true),
            loadPaymentLedger(true, true),
            loadOwnerEquity(true, true),
        ]);
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to load accounting statements.'));
    } finally {
        loading.value = false;
    }
}

async function loadGeneralLedger(resetPage = false, silent = false) {
    if (resetPage) {
        generalLedgerFilters.page = 1;
    }

    try {
        const params = {
            ...ledgerDateParams(),
            per_page: generalLedgerFilters.per_page,
            page: generalLedgerFilters.page,
        };

        const projectId = optionalPositiveInt(generalLedgerFilters.project_id);
        const invoiceId = optionalPositiveInt(generalLedgerFilters.invoice_id);

        if (projectId) {
            params.project_id = projectId;
        }

        if (invoiceId) {
            params.invoice_id = invoiceId;
        }

        const response = await ReportService.generalLedger(params);
        generalLedger.value = response.data;
    } catch (error) {
        if (!silent) {
            toast.error(getApiErrorMessage(error, 'Unable to load general ledger.'));
        }
    }
}

async function loadPaymentLedger(resetPage = false, silent = false) {
    if (resetPage) {
        paymentLedgerFilters.page = 1;
    }

    try {
        const params = {
            ...ledgerDateParams(),
            per_page: paymentLedgerFilters.per_page,
            page: paymentLedgerFilters.page,
        };

        const projectId = optionalPositiveInt(paymentLedgerFilters.project_id);
        const invoiceId = optionalPositiveInt(paymentLedgerFilters.invoice_id);

        if (projectId) {
            params.project_id = projectId;
        }

        if (invoiceId) {
            params.invoice_id = invoiceId;
        }

        const response = await ReportService.paymentLedger(params);
        paymentLedger.value = response.data;
    } catch (error) {
        if (!silent) {
            toast.error(getApiErrorMessage(error, 'Unable to load payment ledger.'));
        }
    }
}

function applyGeneralLedgerFilters() {
    loadGeneralLedger(true);
}

function prevGeneralLedgerPage() {
    if ((generalLedger.pagination?.page ?? 1) <= 1) {
        return;
    }

    generalLedgerFilters.page -= 1;
    loadGeneralLedger(false);
}

function nextGeneralLedgerPage() {
    if ((generalLedger.pagination?.page ?? 1) >= (generalLedger.pagination?.last_page ?? 1)) {
        return;
    }

    generalLedgerFilters.page += 1;
    loadGeneralLedger(false);
}

function applyPaymentLedgerFilters() {
    loadPaymentLedger(true);
}

function prevPaymentLedgerPage() {
    if ((paymentLedger.pagination?.page ?? 1) <= 1) {
        return;
    }

    paymentLedgerFilters.page -= 1;
    loadPaymentLedger(false);
}

function nextPaymentLedgerPage() {
    if ((paymentLedger.pagination?.page ?? 1) >= (paymentLedger.pagination?.last_page ?? 1)) {
        return;
    }

    paymentLedgerFilters.page += 1;
    loadPaymentLedger(false);
}

async function loadOwnerEquity(resetPage = false, silent = false) {
    if (resetPage) {
        ownerEquityFilters.page = 1;
    }

    try {
        const response = await FinanceService.ownerEquityEntries({
            page: ownerEquityFilters.page,
            per_page: ownerEquityFilters.per_page,
        });

        ownerEquity.value = {
            rows: response.data.data ?? [],
            pagination: {
                page: Number(response.data.current_page ?? 1),
                per_page: Number(response.data.per_page ?? ownerEquityFilters.per_page),
                total: Number(response.data.total ?? 0),
                last_page: Number(response.data.last_page ?? 1),
            },
        };

        ownerEquityFilters.page = ownerEquity.value.pagination.page;
    } catch (error) {
        if (!silent) {
            toast.error(getApiErrorMessage(error, 'Unable to load owner equity ledger.'));
        }
    }
}

async function createOwnerEquityEntry() {
    try {
        await FinanceService.createOwnerEquityEntry({
            entry_date: ownerEquityForm.entry_date,
            entry_type: ownerEquityForm.entry_type,
            amount: Number(ownerEquityForm.amount),
            notes: ownerEquityForm.notes || null,
        });

        ownerEquityForm.amount = '';
        ownerEquityForm.notes = '';

        toast.success('Owner equity entry added successfully.');
        await loadAll();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to create owner equity entry.'));
    }
}

function openOwnerEquityEditModal(entry) {
    editOwnerEquityId.value = entry.id;
    ownerEquityEditForm.entry_date = entry.entry_date ?? new Date().toISOString().slice(0, 10);
    ownerEquityEditForm.entry_type = entry.entry_type ?? 'capital_contribution';
    ownerEquityEditForm.amount = String(entry.amount ?? '0');
    ownerEquityEditForm.notes = entry.notes ?? '';
    showOwnerEquityEditModal.value = true;
}

async function submitOwnerEquityEdit() {
    if (!editOwnerEquityId.value) {
        return;
    }

    try {
        await FinanceService.updateOwnerEquityEntry(editOwnerEquityId.value, {
            entry_date: ownerEquityEditForm.entry_date,
            entry_type: ownerEquityEditForm.entry_type,
            amount: Number(ownerEquityEditForm.amount),
            notes: ownerEquityEditForm.notes || null,
        });

        showOwnerEquityEditModal.value = false;
        toast.success('Owner equity entry updated successfully.');
        await loadAll();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to update owner equity entry.'));
    }
}

async function removeOwnerEquityEntry(id) {
    if (!confirm('Delete this owner equity entry?')) {
        return;
    }

    try {
        await FinanceService.deleteOwnerEquityEntry(id);
        toast.success('Owner equity entry deleted successfully.');
        await loadAll();
    } catch (error) {
        toast.error(getApiErrorMessage(error, 'Unable to delete owner equity entry.'));
    }
}

async function onOwnerEquityPerPageChange() {
    ownerEquityFilters.page = 1;
    await loadOwnerEquity(false);
}

async function prevOwnerEquityPage() {
    if ((ownerEquity.value.pagination?.page ?? 1) <= 1) {
        return;
    }

    ownerEquityFilters.page -= 1;
    await loadOwnerEquity(false);
}

async function nextOwnerEquityPage() {
    if ((ownerEquity.value.pagination?.page ?? 1) >= (ownerEquity.value.pagination?.last_page ?? 1)) {
        return;
    }

    ownerEquityFilters.page += 1;
    await loadOwnerEquity(false);
}

function csvValue(value) {
    const text = String(value ?? '');
    return `"${text.replaceAll('"', '""')}"`;
}

function exportCsv(fileName, headers, rows) {
    const lines = [
        headers.map(csvValue).join(','),
        ...rows.map((row) => row.map(csvValue).join(',')),
    ];

    const blob = new Blob([`\uFEFF${lines.join('\n')}`], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', fileName);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function downloadTrialBalancePdf() {
    exportTrialBalancePdf(trialBalance.value);
}

function downloadTrialBalanceCsv() {
    exportCsv(
        `trial-balance-${trialBalance.value.as_of ?? 'as-of'}.csv`,
        ['Account Code', 'Account Name', 'Debit', 'Credit'],
        [
            ...(trialBalance.value.lines ?? []).map((line) => [line.account_code, line.account_name, line.debit, line.credit]),
            ['TOTAL', 'Totals', trialBalance.value.totals?.debit ?? 0, trialBalance.value.totals?.credit ?? 0],
        ],
    );
}

function downloadBalanceSheetPdf() {
    exportBalanceSheetPdf(balanceSheet.value);
}

function downloadBalanceSheetCsv() {
    exportCsv(
        `balance-sheet-${balanceSheet.value.as_of ?? 'as-of'}.csv`,
        ['Section', 'Item', 'Amount'],
        [
            ['Assets', 'Cash and Bank', balanceSheet.value.assets?.cash_and_bank ?? 0],
            ['Assets', 'Accounts Receivable', balanceSheet.value.assets?.accounts_receivable ?? 0],
            ['Assets', 'Prepaid Expenses', balanceSheet.value.assets?.prepaid_expenses ?? 0],
            ['Assets', 'Fixed Assets', balanceSheet.value.assets?.fixed_assets ?? 0],
            ['Liabilities', 'Outstanding Liabilities', balanceSheet.value.liabilities?.outstanding_liabilities ?? 0],
            ['Liabilities', 'Accounts Payable', balanceSheet.value.liabilities?.accounts_payable ?? 0],
            ['Liabilities', 'Bank Overdraft', balanceSheet.value.liabilities?.bank_overdraft ?? 0],
            ['Equity', 'Owner Capital', balanceSheet.value.equity?.owner_capital ?? 0],
            ['Equity', 'Owner Drawings', balanceSheet.value.equity?.owner_drawings ?? 0],
            ['Equity', 'Retained Earnings', balanceSheet.value.equity?.retained_earnings ?? 0],
            ['TOTAL', 'Assets', balanceSheet.value.totals?.assets ?? 0],
            ['TOTAL', 'Liabilities', balanceSheet.value.totals?.liabilities ?? 0],
            ['TOTAL', 'Equity', balanceSheet.value.totals?.equity ?? 0],
        ],
    );
}

function downloadCashFlowPdf() {
    exportCashFlowPdf(cashFlow.value);
}

function downloadCashFlowCsv() {
    exportCsv(
        `cash-flow-${cashFlow.value.from ?? 'from'}-to-${cashFlow.value.to ?? 'to'}.csv`,
        ['Month', 'Opening Balance', 'Collections', 'Operating Outflow', 'Financing Outflow', 'Net Cash Flow', 'Closing Balance'],
        [
            ...(cashFlow.value.rows ?? []).map((row) => [
                row.month,
                row.opening_balance,
                row.cash_in_collections,
                row.operating_outflow,
                row.financing_outflow,
                row.net_cash_flow,
                row.closing_balance,
            ]),
            [
                'TOTAL',
                cashFlow.value.totals?.opening_balance ?? 0,
                cashFlow.value.totals?.cash_in_collections ?? 0,
                cashFlow.value.totals?.operating_outflow ?? 0,
                cashFlow.value.totals?.financing_outflow ?? 0,
                cashFlow.value.totals?.net_cash_flow ?? 0,
                cashFlow.value.totals?.ending_balance ?? 0,
            ],
        ],
    );
}

function downloadGeneralLedgerPdf() {
    exportGeneralLedgerPdf(generalLedger.value);
}

function downloadGeneralLedgerCsv() {
    exportCsv(
        `general-ledger-${generalLedger.value.from ?? 'from'}-to-${generalLedger.value.to ?? 'to'}.csv`,
        ['Date', 'Type', 'Reference', 'Description', 'Project', 'Invoice', 'Debit Account', 'Credit Account', 'Amount'],
        (generalLedger.value.entries ?? []).map((entry) => [
            entry.entry_date,
            entry.entry_type,
            entry.reference,
            entry.description,
            entry.project_name ?? '-',
            entry.invoice_number ?? '-',
            entry.debit_account,
            entry.credit_account,
            entry.amount,
        ]),
    );
}

function downloadPaymentLedgerPdf() {
    exportPaymentLedgerPdf(paymentLedger.value);
}

function downloadPaymentLedgerCsv() {
    exportCsv(
        `payment-ledger-${paymentLedger.value.from ?? 'from'}-to-${paymentLedger.value.to ?? 'to'}.csv`,
        ['Date', 'Project', 'Invoice', 'Client', 'Method', 'Reference', 'Amount', 'Recorded By'],
        (paymentLedger.value.entries ?? []).map((entry) => [
            entry.payment_date,
            entry.project_name ?? '-',
            entry.invoice_number ?? '-',
            entry.client_name ?? '-',
            entry.payment_method ?? '-',
            entry.reference_number ?? '-',
            entry.amount,
            entry.recorded_by ?? '-',
        ]),
    );
}

function number(v) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(Number(v ?? 0));
}

loadAll();
</script>
