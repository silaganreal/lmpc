<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

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

defineProps<{
    stats: Stats;
    installments: PaginatedInstallments;
}>();

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
                                    View Loan
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
    </div>
</template>
