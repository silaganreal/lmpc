<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Account {
    id: number;
    account_code: string;
    account_name: string;
    account_type: string;
    normal_balance: 'debit' | 'credit';
    total_debit: number | string;
    total_credit: number | string;
    debit_balance: number | string;
    credit_balance: number | string;
}

interface Summary {
    total_debits: number;
    total_credits: number;
    difference: number;
    is_balanced: boolean;
}

interface Filters {
    as_of_date: string;
}

const props = defineProps<{
    accounts: Account[];
    summary: Summary;
    filters: Filters;
}>();

const asOfDate = ref(props.filters.as_of_date);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Trial Balance',
        href: '/accounting/trial-balance',
    },
];

const formatAmount = (amount: number | string): string => {
    return Number(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatDate = (date: string): string => {
    if (!date) return '';

    const [year, month, day] = date.split('-');

    return `${month}/${day}/${year}`;
};

const applyFilter = () => {
    router.get(
        '/accounting/trial-balance',
        { as_of_date: asOfDate.value },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const printReport = () => {
    window.print();
};

const accountCount = computed(() => props.accounts.length);

const generatedAt = new Date().toLocaleString('en-PH', {
    dateStyle: 'long',
    timeStyle: 'short',
});
</script>

<template>
    <Head title="Trial Balance" />

    <div class="print-area flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Print Header: Visible only when printing -->
        <div class="print-header hidden text-center">
            <h1 class="text-xl font-bold uppercase">
                Landbankers Multi-Purpose Cooperative
            </h1>
            <p class="text-sm">Cooperative Management System</p>

            <div class="mt-5">
                <h2 class="text-lg font-bold uppercase">Trial Balance</h2>
                <p class="text-sm">
                    As of {{ formatDate(filters.as_of_date) }}
                </p>
            </div>
        </div>

        <!-- Screen Header -->
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center print:hidden"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Trial Balance
                </h1>
                <p class="text-muted-foreground text-sm">
                    Summary of debit and credit balances across active posting
                    accounts.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <div class="space-y-1">
                    <label for="as-of-date" class="text-sm font-medium">
                        As of Date
                    </label>

                    <input
                        id="as-of-date"
                        v-model="asOfDate"
                        type="date"
                        class="border-input bg-background h-9 rounded-md border px-3 text-sm shadow-sm"
                    />
                </div>

                <button
                    type="button"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex h-9 items-center justify-center rounded-md px-4 text-sm font-medium shadow transition-colors"
                    @click="applyFilter"
                >
                    Generate
                </button>

                <button
                    type="button"
                    class="hover:bg-muted inline-flex h-9 items-center justify-center gap-2 rounded-md border px-4 text-sm font-medium shadow-sm transition-colors"
                    @click="printReport"
                >
                    <span>🖨</span>
                    Print Report
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 print:hidden">
            <div class="bg-card rounded-xl border p-5 shadow-sm">
                <p class="text-muted-foreground text-sm font-medium">
                    Total Debit
                </p>
                <p class="mt-2 text-2xl font-semibold tabular-nums">
                    ₱{{ formatAmount(summary.total_debits) }}
                </p>
            </div>

            <div class="bg-card rounded-xl border p-5 shadow-sm">
                <p class="text-muted-foreground text-sm font-medium">
                    Total Credit
                </p>
                <p class="mt-2 text-2xl font-semibold tabular-nums">
                    ₱{{ formatAmount(summary.total_credits) }}
                </p>
            </div>

            <div class="bg-card rounded-xl border p-5 shadow-sm">
                <p class="text-muted-foreground text-sm font-medium">
                    Difference
                </p>
                <p class="mt-2 text-2xl font-semibold tabular-nums">
                    ₱{{ formatAmount(summary.difference) }}
                </p>
            </div>

            <div class="bg-card rounded-xl border p-5 shadow-sm">
                <p class="text-muted-foreground text-sm font-medium">
                    Trial Balance Status
                </p>

                <div class="mt-2 flex items-center gap-2">
                    <span
                        class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
                        :class="
                            summary.is_balanced
                                ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400'
                                : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400'
                        "
                    >
                        {{ summary.is_balanced ? 'Balanced' : 'Unbalanced' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Print Summary -->
        <div class="print-summary hidden">
            <div class="flex justify-between border-y py-3 text-sm">
                <div>
                    <strong>Total Debit:</strong>
                    ₱{{ formatAmount(summary.total_debits) }}
                </div>

                <div>
                    <strong>Total Credit:</strong>
                    ₱{{ formatAmount(summary.total_credits) }}
                </div>

                <div>
                    <strong>Difference:</strong>
                    ₱{{ formatAmount(summary.difference) }}
                </div>
            </div>

            <p class="mt-2 text-right text-sm font-bold">
                Status:
                {{ summary.is_balanced ? 'BALANCED' : 'UNBALANCED' }}
            </p>
        </div>

        <!-- Trial Balance Table -->
        <div
            class="bg-card print-table overflow-hidden rounded-xl border shadow-sm"
        >
            <div
                class="flex flex-col justify-between gap-2 border-b p-4 sm:flex-row sm:items-center print:hidden"
            >
                <div>
                    <h2 class="font-semibold">Account Balances</h2>
                    <p class="text-muted-foreground text-sm">
                        {{ accountCount }} active posting accounts
                    </p>
                </div>

                <p class="text-muted-foreground text-sm">
                    As of {{ filters.as_of_date }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="print-table-content w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr class="border-b">
                            <th class="px-4 py-3 text-left font-medium">
                                Account Code
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Account Name
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Account Type
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Debit Balance
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Credit Balance
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="account in accounts"
                            :key="account.id"
                            class="hover:bg-muted/30 border-b transition-colors"
                        >
                            <td
                                class="text-muted-foreground px-4 py-3 font-mono whitespace-nowrap"
                            >
                                {{ account.account_code }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ account.account_name }}
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="bg-muted print-account-type rounded-md px-2 py-1 text-xs"
                                >
                                    {{ account.account_type }}
                                </span>
                            </td>

                            <td
                                class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                            >
                                {{
                                    Number(account.debit_balance) > 0
                                        ? formatAmount(account.debit_balance)
                                        : '—'
                                }}
                            </td>

                            <td
                                class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                            >
                                {{
                                    Number(account.credit_balance) > 0
                                        ? formatAmount(account.credit_balance)
                                        : '—'
                                }}
                            </td>
                        </tr>

                        <tr v-if="accounts.length === 0">
                            <td
                                colspan="5"
                                class="text-muted-foreground px-4 py-12 text-center"
                            >
                                No active posting accounts found.
                            </td>
                        </tr>
                    </tbody>

                    <tfoot class="bg-muted/50 border-t-2 font-semibold">
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-right">
                                TOTAL
                            </td>

                            <td
                                class="px-4 py-4 text-right whitespace-nowrap tabular-nums"
                            >
                                ₱{{ formatAmount(summary.total_debits) }}
                            </td>

                            <td
                                class="px-4 py-4 text-right whitespace-nowrap tabular-nums"
                            >
                                ₱{{ formatAmount(summary.total_credits) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Status -->
            <div
                class="flex flex-col justify-between gap-2 border-t p-4 text-sm sm:flex-row sm:items-center print:hidden"
            >
                <p class="text-muted-foreground">
                    Trial Balance generated as of {{ filters.as_of_date }}.
                </p>

                <p
                    class="font-medium"
                    :class="
                        summary.is_balanced
                            ? 'text-green-600 dark:text-green-400'
                            : 'text-red-600 dark:text-red-400'
                    "
                >
                    {{
                        summary.is_balanced
                            ? 'Total debits equal total credits.'
                            : 'Total debits do not equal total credits.'
                    }}
                </p>
            </div>
        </div>

        <!-- Print Signature Section -->
        <div class="print-signatures hidden">
            <div class="grid grid-cols-3 gap-8 text-center text-sm">
                <div>
                    <div class="signature-line"></div>
                    <p class="font-semibold">Prepared by</p>
                </div>

                <div>
                    <div class="signature-line"></div>
                    <p class="font-semibold">Checked by</p>
                </div>

                <div>
                    <div class="signature-line"></div>
                    <p class="font-semibold">Approved by</p>
                </div>
            </div>

            <p class="mt-8 text-center text-xs">
                Generated on {{ generatedAt }}
            </p>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: landscape;
        margin: 12mm;
    }

    body * {
        visibility: hidden !important;
    }

    .print-area,
    .print-area * {
        visibility: visible !important;
    }

    .print-area {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: auto !important;
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
        color: #000 !important;
        background: #fff !important;
    }

    .print-header,
    .print-summary,
    .print-signatures {
        display: block !important;
    }

    .print-header {
        margin-bottom: 20px;
    }

    .print-table {
        width: 100% !important;
        overflow: visible !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .print-table-content {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 10pt !important;
    }

    .print-table-content thead {
        display: table-header-group;
    }

    .print-table-content tr {
        break-inside: avoid;
    }

    .print-table-content th,
    .print-table-content td {
        padding: 7px 9px !important;
        border: 1px solid #999 !important;
        color: #000 !important;
    }

    .print-table-content thead,
    .print-table-content tfoot {
        background: #eee !important;
    }

    .print-table-content tfoot {
        display: table-row-group !important;
    }

    .print-account-type {
        background: transparent !important;
        padding: 0 !important;
    }

    .print-signatures {
        margin-top: 55px;
        break-inside: avoid;
    }

    .signature-line {
        height: 35px;
        margin-bottom: 8px;
        border-bottom: 1px solid #000;
    }

    .print-area .overflow-x-auto {
        overflow: visible !important;
    }
}
</style>
