<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Users, FileText, Wallet, CreditCard } from '@lucide/vue';

import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

interface DashboardStats {
    members: {
        total: number;
        active: number;
    };
    applications: {
        total: number;
        draft: number;
        submitted: number;
        underReview: number;
        approved: number;
        awaitingAction: number;
    };
    shareCapital: {
        paidUp: number;
    };
    cbu: {
        balance: number;
    };
    savings: {
        balance: number;
    };
    loans: {
        total: number;
        pendingApproval: number;
        awaitingRelease: number;
        active: number;
        pastDue: number;
        outstandingPrincipal: number;
    };
    collections: CollectionStats;
}

interface CollectionStats {
    overdueInstallments: number;
    thisMonth: number;
}

interface RecentPayment {
    id: number;
    payment_date: string;
    reference_no: string | null;
    payment_method: string;
    total_amount: number | string;
    loan: {
        id: number;
        loan_no: string;
        member: {
            member_no: string;
            first_name: string;
            middle_name: string | null;
            last_name: string;
        };
    };
}

defineProps<{
    stats: DashboardStats;
    recentPayments: RecentPayment[];
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

const memberName = (member: RecentPayment['loan']['member']) =>
    [member.first_name, member.middle_name, member.last_name]
        .filter(Boolean)
        .join(' ');
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Welcome -->
        <div>
            <h1 class="text-primary text-2xl font-semibold tracking-tight">
                Dashboard
            </h1>

            <p class="text-muted-foreground mt-1 text-sm">
                Welcome to the LMPC Cooperative Management System.
            </p>
        </div>

        <!-- Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Members -->
            <Link
                href="/members"
                class="group bg-card hover:border-primary/40 rounded-xl border p-5 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div
                        class="bg-secondary text-primary flex size-10 items-center justify-center rounded-lg"
                    >
                        <Users class="size-5" />
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-muted-foreground text-sm">Members</p>

                    <p class="mt-1 text-2xl font-semibold">
                        {{ stats.members.active }}
                    </p>

                    <p class="text-muted-foreground mt-1 text-xs">
                        View member records
                    </p>
                </div>
            </Link>

            <!-- Applications -->
            <div class="bg-card rounded-xl border p-5 shadow-sm">
                <div
                    class="bg-secondary text-primary flex size-10 items-center justify-center rounded-lg"
                >
                    <FileText class="size-5" />
                </div>

                <div class="mt-4">
                    <p class="text-muted-foreground text-sm">Applications</p>

                    <p class="mt-1 text-2xl font-semibold">
                        {{ stats.applications.awaitingAction }}
                    </p>

                    <p class="text-muted-foreground mt-1 text-xs">
                        Submitted and under review
                    </p>
                </div>
            </div>

            <!-- CBU Balance -->
            <div class="bg-card rounded-xl border p-5 shadow-sm">
                <div
                    class="bg-secondary text-primary flex size-10 items-center justify-center rounded-lg"
                >
                    <Wallet class="size-5" />
                </div>

                <div class="mt-4">
                    <p class="text-muted-foreground text-sm">CBU Balance</p>

                    <p class="mt-1 text-2xl font-semibold">
                        {{ money(stats.cbu.balance) }}
                    </p>

                    <p class="text-muted-foreground mt-1 text-xs">
                        Total members' capital build-up
                    </p>
                </div>
            </div>

            <!-- Loans -->
            <div class="bg-card rounded-xl border p-5 shadow-sm">
                <div
                    class="bg-secondary text-primary flex size-10 items-center justify-center rounded-lg"
                >
                    <CreditCard class="size-5" />
                </div>

                <div class="mt-4">
                    <p class="text-muted-foreground text-sm">Loans</p>

                    <p class="mt-1 text-2xl font-semibold">
                        {{ money(stats.loans.outstandingPrincipal) }}
                    </p>

                    <p class="text-muted-foreground mt-1 text-xs">
                        Loan portfolio
                    </p>
                </div>
            </div>
        </div>

        <!-- Collection Summary -->
        <div class="grid gap-4 sm:grid-cols-2">
            <Link
                href="/collections"
                class="bg-card hover:border-primary/40 rounded-xl border p-5 shadow-sm transition hover:shadow-md"
            >
                <p class="text-muted-foreground text-sm">
                    Overdue Installments
                </p>

                <p
                    class="mt-2 text-2xl font-semibold"
                    :class="
                        stats.collections.overdueInstallments > 0
                            ? 'text-destructive'
                            : ''
                    "
                >
                    {{ stats.collections.overdueInstallments }}
                </p>

                <p class="text-muted-foreground mt-1 text-xs">
                    Unpaid or partially paid past-due installments
                </p>
            </Link>

            <Link
                href="/collections"
                class="bg-card hover:border-primary/40 rounded-xl border p-5 shadow-sm transition hover:shadow-md"
            >
                <p class="text-muted-foreground text-sm">
                    Collections This Month
                </p>

                <p class="mt-2 text-2xl font-semibold">
                    {{ money(stats.collections.thisMonth) }}
                </p>

                <p class="text-muted-foreground mt-1 text-xs">
                    Recorded loan payments this calendar month
                </p>
            </Link>
        </div>

        <!-- Recent Collections -->
        <div class="bg-card overflow-hidden rounded-xl border shadow-sm">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"
            >
                <div>
                    <h2 class="font-semibold">Recent Collections</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Latest five recorded loan payments
                    </p>
                </div>

                <Link
                    href="/collections"
                    class="text-primary text-sm font-medium hover:underline"
                >
                    View all collections
                </Link>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Payment Date</th>
                            <th class="px-4 py-3 font-medium">Loan No.</th>
                            <th class="px-4 py-3 font-medium">Member</th>
                            <th class="px-4 py-3 font-medium">Reference</th>
                            <th class="px-4 py-3 font-medium">Method</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Amount
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr
                            v-for="payment in recentPayments"
                            :key="payment.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDate(payment.payment_date) }}
                            </td>

                            <td class="px-4 py-3">
                                <Link
                                    :href="`/loans/${payment.loan.id}`"
                                    class="text-primary font-medium hover:underline"
                                >
                                    {{ payment.loan.loan_no }}
                                </Link>
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ memberName(payment.loan.member) }}
                                </div>
                                <div class="text-muted-foreground text-xs">
                                    {{ payment.loan.member.member_no }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                {{ payment.reference_no || '—' }}
                            </td>

                            <td class="px-4 py-3 capitalize">
                                {{
                                    payment.payment_method.replaceAll('_', ' ')
                                }}
                            </td>

                            <td
                                class="px-4 py-3 text-right font-semibold whitespace-nowrap"
                            >
                                {{ money(payment.total_amount) }}
                            </td>
                        </tr>

                        <tr v-if="recentPayments.length === 0">
                            <td
                                colspan="6"
                                class="text-muted-foreground px-4 py-10 text-center"
                            >
                                No loan payments have been recorded yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- System Overview -->
        <div class="bg-card rounded-xl border shadow-sm">
            <div class="border-b px-5 py-4">
                <h2 class="font-semibold">System Overview</h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    Monitor cooperative operations and access key modules.
                </p>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <!-- Membership Applications -->
                <Link
                    href="/membership-applications"
                    class="hover:border-primary/40 hover:bg-secondary/40 rounded-lg border p-4 transition"
                >
                    <div class="flex items-start gap-3">
                        <FileText class="text-primary mt-1 size-5" />

                        <div class="flex-1">
                            <p class="font-medium">Membership Applications</p>
                            <p class="text-muted-foreground mt-1 text-sm">
                                {{ stats.applications.awaitingAction }}
                                application(s) awaiting action
                            </p>

                            <div
                                class="text-muted-foreground mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs"
                            >
                                <span
                                    >Draft: {{ stats.applications.draft }}</span
                                >
                                <span
                                    >Submitted:
                                    {{ stats.applications.submitted }}</span
                                >
                                <span
                                    >Under review:
                                    {{ stats.applications.underReview }}</span
                                >
                            </div>
                        </div>
                    </div>
                </Link>

                <!-- Manage Members -->
                <Link
                    href="/members"
                    class="hover:border-primary/40 hover:bg-secondary/40 rounded-lg border p-4 transition"
                >
                    <div class="flex items-start gap-3">
                        <Users class="text-primary mt-1 size-5" />

                        <div>
                            <p class="font-medium">Manage Members</p>
                            <p class="text-muted-foreground mt-1 text-sm">
                                {{ stats.members.active }} active member(s)
                            </p>
                            <p class="text-muted-foreground mt-1 text-xs">
                                View and manage cooperative member records.
                            </p>
                        </div>
                    </div>
                </Link>

                <!-- Loan Workload -->
                <div class="rounded-lg border p-4">
                    <div class="flex items-start gap-3">
                        <CreditCard class="text-primary mt-1 size-5" />

                        <div class="flex-1">
                            <p class="font-medium">Loan Applications</p>
                            <p class="text-muted-foreground mt-1 text-sm">
                                Monitor applications requiring action.
                            </p>

                            <div
                                class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs"
                            >
                                <span>
                                    Pending approval:
                                    {{ stats.loans.pendingApproval }}
                                </span>
                                <span>
                                    Approved, awaiting release:
                                    {{ stats.loans.awaitingRelease }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Collections -->
                <Link
                    href="/collections"
                    class="hover:border-primary/40 hover:bg-secondary/40 rounded-lg border p-4 transition"
                >
                    <div class="flex items-start gap-3">
                        <Wallet class="text-primary mt-1 size-5" />

                        <div>
                            <p class="font-medium">Collections</p>
                            <p class="text-muted-foreground mt-1 text-sm">
                                {{ stats.collections.overdueInstallments }}
                                overdue installment(s)
                            </p>
                            <p class="text-muted-foreground mt-1 text-xs">
                                Review installments and payment transactions.
                            </p>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
