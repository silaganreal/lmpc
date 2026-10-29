<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref } from 'vue';

interface Stats {
    unpaidInstallments: number;
    partialInstallments: number;
    overdueInstallments: number;
    collectedThisMonth: number;
}

interface Installment {
    id: number;
    installment_no: number;
    due_date: string;
    total_due: number | string;
    paid_amount: number | string;
    status: 'pending' | 'partial' | 'paid';
    loan: {
        id: number;
        loan_no: string;
        member: {
            id: number;
            first_name?: string;
            middle_name?: string | null;
            last_name?: string;
            member_no?: string;
        };
        loan_product: {
            name: string;
        };
    };
}

interface PaginatedInstallments {
    data: Installment[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface Payment {
    id: number;
    payment_date: string;
    reference_no: string | null;
    principal_amount: number | string;
    interest_amount: number | string;
    penalty_amount: number | string;
    total_amount: number | string;
    payment_method: string;
    remarks: string | null;
    loan: {
        id: number;
        loan_no: string;
        member: {
            id: number;
            first_name?: string;
            middle_name?: string | null;
            last_name?: string;
            member_no?: string;
        };
    };
    created_by: {
        id: number;
        name: string;
    } | null;
}

interface PaginatedPayments {
    data: Payment[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface CollectionTotals {
    transactionCount: number;
    totalAmount: number;
    principalAmount: number;
    interestAmount: number;
    penaltyAmount: number;
}

interface CollectionFilters {
    date_from: string;
    date_to: string;
}

const props = defineProps<{
    stats: Stats;
    installments: PaginatedInstallments;
    payments: PaginatedPayments;
    collectionTotals: CollectionTotals;
    filters: CollectionFilters;
}>();

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

const applyFilters = () => {
    router.get(
        route('collections.index'),
        {
            date_from: dateFrom.value,
            date_to: dateTo.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    dateFrom.value = '';
    dateTo.value = '';

    router.get(
        route('collections.index'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const money = (value: number | string) =>
    new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(value ?? 0));

const formatDate = (value: string) => {
    const date = value.includes('T') ? value : `${value}T00:00:00`;

    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(date));
};

const memberName = (member: Installment['loan']['member']) =>
    [member.first_name, member.middle_name, member.last_name]
        .filter(Boolean)
        .join(' ') || 'Unknown member';

const balance = (installment: Installment) =>
    Math.max(
        0,
        Number(installment.total_due) - Number(installment.paid_amount),
    );

const isOverdue = (installment: Installment) =>
    installment.status !== 'paid' &&
    installment.due_date < new Date().toLocaleDateString('en-CA');
</script>

<template>
    <Head title="Collections" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Collections</h1>
            <p class="text-muted-foreground mt-1 text-sm">
                Monitor loan installments, overdue accounts, and payments.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Unpaid Installments</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ stats.unpaidInstallments }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">
                    Partial Installments
                </p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ stats.partialInstallments }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">
                    Overdue Installments
                </p>
                <p class="text-destructive mt-2 text-2xl font-semibold">
                    {{ stats.overdueInstallments }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">
                    Collected This Month
                </p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ money(stats.collectedThisMonth) }}
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b p-4"
            >
                <div>
                    <h2 class="font-semibold">Installment Monitoring</h2>
                    <p class="text-muted-foreground text-sm">
                        {{ installments.total }} unpaid or partially paid
                        installments
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Loan / Member</th>
                            <th class="px-4 py-3 font-medium">Installment</th>
                            <th class="px-4 py-3 font-medium">Due Date</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Amount Due
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Paid
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Balance
                            </th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr
                            v-for="installment in installments.data"
                            :key="installment.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ installment.loan.loan_no }}
                                </div>

                                <div class="text-muted-foreground">
                                    {{
                                        installment.loan.loan_product?.name ??
                                        'Unknown loan product'
                                    }}
                                </div>

                                <div class="text-muted-foreground">
                                    {{ memberName(installment.loan.member) }}
                                </div>

                                <div class="text-muted-foreground text-xs">
                                    Member No.:
                                    {{
                                        installment.loan.member.member_no ?? '—'
                                    }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                #{{ installment.installment_no }}
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    :class="
                                        isOverdue(installment)
                                            ? 'text-destructive font-medium'
                                            : ''
                                    "
                                >
                                    {{ formatDate(installment.due_date) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                {{ money(installment.total_due) }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                {{ money(installment.paid_amount) }}
                            </td>

                            <td class="px-4 py-3 text-right font-medium">
                                {{ money(balance(installment)) }}
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200':
                                            installment.status === 'pending',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200':
                                            installment.status === 'partial',
                                    }"
                                >
                                    {{
                                        isOverdue(installment)
                                            ? 'Overdue'
                                            : installment.status === 'partial'
                                              ? 'Partial'
                                              : 'Pending'
                                    }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <Link
                                    :href="
                                        route('loans.show', installment.loan.id)
                                    "
                                    class="text-primary font-medium hover:underline"
                                >
                                    Record Payment
                                </Link>
                            </td>
                        </tr>

                        <tr v-if="installments.data.length === 0">
                            <td
                                colspan="8"
                                class="text-muted-foreground px-4 py-10 text-center"
                            >
                                No unpaid installments found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between gap-3 border-t p-4">
                <span class="text-muted-foreground text-sm">
                    Page {{ installments.current_page }} of
                    {{ installments.last_page }}
                </span>

                <div class="flex gap-2">
                    <Link
                        v-if="installments.prev_page_url"
                        :href="installments.prev_page_url"
                        class="hover:bg-muted rounded-md border px-3 py-2 text-sm"
                    >
                        Previous
                    </Link>

                    <Link
                        v-if="installments.next_page_url"
                        :href="installments.next_page_url"
                        class="hover:bg-muted rounded-md border px-3 py-2 text-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <div class="border-b p-4">
                <h2 class="font-semibold">Payment Transactions</h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ payments.total }} payment records for the selected period
                </p>
            </div>

            <form
                class="flex flex-wrap items-end gap-3 border-b p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-1">
                    <label for="date_from" class="text-sm font-medium">
                        From
                    </label>
                    <input
                        id="date_from"
                        v-model="dateFrom"
                        type="date"
                        required
                        class="bg-background rounded-md border px-3 py-2 text-sm"
                    />
                </div>

                <div class="grid gap-1">
                    <label for="date_to" class="text-sm font-medium">
                        To
                    </label>
                    <input
                        id="date_to"
                        v-model="dateTo"
                        type="date"
                        required
                        :min="dateFrom || undefined"
                        class="bg-background rounded-md border px-3 py-2 text-sm"
                    />
                </div>

                <button
                    type="submit"
                    class="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm font-medium"
                >
                    Apply Filters
                </button>

                <button
                    type="button"
                    @click="resetFilters"
                    class="rounded-md border px-4 py-2 text-sm"
                >
                    Reset
                </button>
            </form>

            <div class="grid gap-4 border-b p-4 sm:grid-cols-2 xl:grid-cols-5">
                <div>
                    <p class="text-muted-foreground text-sm">Transactions</p>
                    <p class="mt-1 text-xl font-semibold">
                        {{ collectionTotals.transactionCount }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Total Collected</p>
                    <p class="mt-1 text-xl font-semibold">
                        {{ money(collectionTotals.totalAmount) }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Principal</p>
                    <p class="mt-1 font-semibold">
                        {{ money(collectionTotals.principalAmount) }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Interest</p>
                    <p class="mt-1 font-semibold">
                        {{ money(collectionTotals.interestAmount) }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Penalty</p>
                    <p class="mt-1 font-semibold">
                        {{ money(collectionTotals.penaltyAmount) }}
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Loan / Member</th>
                            <th class="px-4 py-3 font-medium">Reference</th>
                            <th class="px-4 py-3 font-medium">Method</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Principal
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Interest
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Penalty
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Total
                            </th>
                            <th class="px-4 py-3 font-medium">Recorded By</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr
                            v-for="payment in payments.data"
                            :key="payment.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDate(payment.payment_date) }}
                            </td>

                            <td class="px-4 py-3">
                                <Link
                                    :href="route('loans.show', payment.loan.id)"
                                    class="text-primary font-medium hover:underline"
                                >
                                    {{ payment.loan.loan_no }}
                                </Link>
                                <div class="text-muted-foreground">
                                    {{ memberName(payment.loan.member) }}
                                </div>
                                <div class="text-muted-foreground text-xs">
                                    Member No.:
                                    {{ payment.loan.member.member_no ?? '—' }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                {{ payment.reference_no || '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{
                                    payment.payment_method.replaceAll('_', ' ')
                                }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{ money(payment.principal_amount) }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{ money(payment.interest_amount) }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{ money(payment.penalty_amount) }}
                            </td>

                            <td
                                class="px-4 py-3 text-right font-semibold whitespace-nowrap"
                            >
                                {{ money(payment.total_amount) }}
                            </td>

                            <td class="px-4 py-3">
                                {{
                                    payment.created_by?.name ??
                                    'System / unavailable'
                                }}
                            </td>
                        </tr>

                        <tr v-if="payments.data.length === 0">
                            <td
                                colspan="9"
                                class="text-muted-foreground px-4 py-10 text-center"
                            >
                                No payment transactions found for this period.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between gap-3 border-t p-4">
                <span class="text-muted-foreground text-sm">
                    Page {{ payments.current_page }} of {{ payments.last_page }}
                </span>

                <div class="flex gap-2">
                    <Link
                        v-if="payments.prev_page_url"
                        :href="payments.prev_page_url"
                        class="hover:bg-muted rounded-md border px-3 py-2 text-sm"
                    >
                        Previous
                    </Link>

                    <Link
                        v-if="payments.next_page_url"
                        :href="payments.next_page_url"
                        class="hover:bg-muted rounded-md border px-3 py-2 text-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
