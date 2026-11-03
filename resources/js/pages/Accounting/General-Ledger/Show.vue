<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed, ref } from 'vue';
import { ArrowLeft, CalendarDays, Filter } from 'lucide-vue-next';

interface Account {
    id: number;
    account_code: string | null;
    account_name: string;
    account_type: string;
    normal_balance: 'debit' | 'credit';
}

interface Transaction {
    id: number;
    journal_entry_id: number;
    line_number: number;
    line_description: string | null;
    debit: string | number;
    credit: string | number;
    entry_number: string;
    entry_date: string;
    reference_number: string | null;
    entry_description: string;
    status: 'posted' | 'reversed';
    running_balance: number;
}

interface Filters {
    start_date: string;
    end_date: string;
}

interface Summary {
    total_debit: number;
    total_credit: number;
    closing_balance: number;
}

const props = defineProps<{
    account: Account;
    openingBalance: number;
    transactions: Transaction[];
    summary: Summary;
    filters: Filters;
}>();

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

const currencyFormatter = new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const formatAmount = (amount: string | number) =>
    currencyFormatter.format(Number(amount));

const formatDate = (date: string) => {
    const parsedDate = new Date(`${date.substring(0, 10)}T00:00:00`);

    return parsedDate.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    });
};

const formatBalance = (balance: number) => {
    const side =
        balance === 0
            ? ''
            : balance > 0
              ? props.account.normal_balance.toUpperCase()
              : props.account.normal_balance === 'debit'
                ? 'CREDIT'
                : 'DEBIT';

    return `${formatAmount(Math.abs(balance))}${side ? ` ${side}` : ''}`;
};

const openingBalance = computed(() => formatBalance(props.openingBalance));

const closingBalance = computed(() =>
    formatBalance(props.summary.closing_balance),
);

const applyDateFilter = () => {
    router.get(
        route('accounting.general-ledger.show', props.account.id),
        {
            start_date: startDate.value,
            end_date: endDate.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetDateFilter = () => {
    startDate.value = '';
    endDate.value = '';

    router.get(
        route('accounting.general-ledger.show', props.account.id),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const statusClass = (status: string) =>
    status === 'reversed'
        ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'
        : 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200';
</script>

<template>
    <Head :title="`General Ledger - ${account.account_name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Link
                    :href="route('accounting.general-ledger.index')"
                    class="text-muted-foreground hover:text-foreground mb-3 inline-flex items-center gap-2 text-sm"
                >
                    <ArrowLeft class="size-4" />
                    Back to General Ledger
                </Link>

                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ account.account_name }}
                </h1>

                <p class="text-muted-foreground mt-1 text-sm">
                    Account Code:
                    <span class="font-medium">
                        {{ account.account_code || '—' }}
                    </span>
                    <span class="mx-2">·</span>
                    {{ account.account_type }}
                    <span class="mx-2">·</span>
                    Normal Balance:
                    {{ account.normal_balance }}
                </p>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="rounded-xl border p-4">
            <div class="mb-4 flex items-center gap-2">
                <CalendarDays class="text-muted-foreground size-5" />
                <h2 class="font-semibold">Statement Period</h2>
            </div>

            <form
                class="flex flex-wrap items-end gap-3"
                @submit.prevent="applyDateFilter"
            >
                <div class="grid min-w-[180px] flex-1 gap-1">
                    <label for="start_date" class="text-sm font-medium">
                        Start Date
                    </label>

                    <input
                        id="start_date"
                        v-model="startDate"
                        type="date"
                        required
                        class="bg-background rounded-md border px-3 py-2 text-sm"
                    />
                </div>

                <div class="grid min-w-[180px] flex-1 gap-1">
                    <label for="end_date" class="text-sm font-medium">
                        End Date
                    </label>

                    <input
                        id="end_date"
                        v-model="endDate"
                        type="date"
                        required
                        :min="startDate"
                        class="bg-background rounded-md border px-3 py-2 text-sm"
                    />
                </div>

                <button
                    type="submit"
                    class="bg-primary text-primary-foreground inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium"
                >
                    <Filter class="size-4" />
                    Apply Filter
                </button>

                <button
                    type="button"
                    @click="resetDateFilter"
                    class="rounded-md border px-4 py-2 text-sm"
                >
                    Reset
                </button>
            </form>
        </div>

        <!-- Balance Summary -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Opening Balance</p>
                <p class="mt-2 text-xl font-semibold tabular-nums">
                    {{ openingBalance }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Total Debits</p>
                <p class="mt-2 text-xl font-semibold tabular-nums">
                    {{ formatAmount(summary.total_debit) }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Total Credits</p>
                <p class="mt-2 text-xl font-semibold tabular-nums">
                    {{ formatAmount(summary.total_credit) }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Closing Balance</p>
                <p class="mt-2 text-xl font-semibold tabular-nums">
                    {{ closingBalance }}
                </p>
            </div>
        </div>

        <!-- Transaction Register -->
        <div class="overflow-hidden rounded-xl border">
            <div class="border-b p-4">
                <h2 class="font-semibold">Ledger Transactions</h2>

                <p class="text-muted-foreground mt-1 text-sm">
                    {{ formatDate(filters.start_date) }}
                    to
                    {{ formatDate(filters.end_date) }}
                    · {{ transactions.length }} transaction lines
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Journal Entry</th>
                            <th class="px-4 py-3 font-medium">Reference</th>
                            <th class="px-4 py-3 font-medium">Description</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Debit
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Credit
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Running Balance
                            </th>
                            <th class="px-4 py-3 text-center font-medium">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <!-- Opening Balance -->
                        <tr class="bg-muted/30 font-medium">
                            <td colspan="6" class="px-4 py-3 text-right">
                                Opening Balance
                            </td>

                            <td
                                class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                            >
                                {{ openingBalance }}
                            </td>

                            <td></td>
                        </tr>

                        <!-- Transactions -->
                        <tr
                            v-for="transaction in transactions"
                            :key="transaction.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDate(transaction.entry_date) }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <Link
                                    :href="
                                        route(
                                            'accounting.journal-entries.show',
                                            transaction.journal_entry_id,
                                        )
                                    "
                                    class="text-primary font-medium hover:underline"
                                >
                                    {{ transaction.entry_number }}
                                </Link>
                            </td>

                            <td class="px-4 py-3">
                                {{ transaction.reference_number || '—' }}
                            </td>

                            <td class="min-w-[200px] px-4 py-3">
                                {{
                                    transaction.line_description ||
                                    transaction.entry_description ||
                                    '—'
                                }}
                            </td>

                            <td
                                class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                            >
                                {{
                                    Number(transaction.debit) > 0
                                        ? formatAmount(transaction.debit)
                                        : '—'
                                }}
                            </td>

                            <td
                                class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                            >
                                {{
                                    Number(transaction.credit) > 0
                                        ? formatAmount(transaction.credit)
                                        : '—'
                                }}
                            </td>

                            <td
                                class="px-4 py-3 text-right font-medium whitespace-nowrap tabular-nums"
                            >
                                {{ formatBalance(transaction.running_balance) }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                                    :class="statusClass(transaction.status)"
                                >
                                    {{ transaction.status }}
                                </span>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="transactions.length === 0">
                            <td
                                colspan="8"
                                class="text-muted-foreground px-4 py-10 text-center"
                            >
                                No transactions found for the selected period.
                            </td>
                        </tr>

                        <!-- Closing Balance -->
                        <tr class="bg-muted/30 font-semibold">
                            <td colspan="6" class="px-4 py-3 text-right">
                                Closing Balance
                            </td>

                            <td
                                class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                            >
                                {{ closingBalance }}
                            </td>

                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-muted-foreground border-t p-4 text-xs">
                Balances are presented according to the account's normal
                balance. Reversed journal entries and their corresponding
                reversal entries are included in the ledger.
            </div>
        </div>
    </div>
</template>
