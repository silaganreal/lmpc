<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

interface Member {
    id: number;
    member_no: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
}

interface LoanProduct {
    id: number;
    code: string;
    name: string;
}

interface LoanAmortization {
    id: number;
    installment_no: number;
    due_date: string;
    beginning_balance: string | number;
    principal_amount: string | number;
    interest_amount: string | number;
    penalty_amount: string | number;
    total_due: string | number;
    paid_amount: string | number;
    paid_date: string | null;
    status: string;
}

interface Loan {
    id: number;
    loan_no: string;
    principal_amount: string | number;
    cbu_retention: string | number;
    service_fee: string | number;
    insurance_premium: string | number;
    notarial_fee: string | number;
    net_process: string | number;
    term_months: number;
    status: string;
    purpose: string | null;
    remarks: string | null;

    application_date: string;
    approval_date: string | null;
    release_date: string | null;

    interest_rate_monthly: string | number;
    monthly_amortization: string | number | null;

    outstanding_principal: string | number;
    outstanding_interest: string | number;
    outstanding_penalty: string | number;

    member: Member;
    loan_product: LoanProduct;
    amortizations: LoanAmortization[];
}

interface Props {
    loan: Loan;
}

const props = defineProps<Props>();

const formatCurrency = (value: number | string) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(Number(value) || 0);
};

const formatDate = (value: string) => {
    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(value));
};

const memberName = computed(() => {
    const member = props.loan.member;

    return [member.first_name, member.middle_name, member.last_name]
        .filter(Boolean)
        .join(' ');
});

const statusLabel = computed(() => {
    return (
        props.loan.status.charAt(0).toUpperCase() + props.loan.status.slice(1)
    );
});

const totalInterest = computed(() => {
    const principal = Number(props.loan.principal_amount) || 0;
    const monthlyRate = Number(props.loan.interest_rate_monthly) || 0;
    const term = Number(props.loan.term_months) || 0;

    return principal * (monthlyRate / 100) * term;
});

const totalPayable = computed(() => {
    const principal = Number(props.loan.principal_amount) || 0;

    return principal + totalInterest.value;
});

const submitApplication = () => {
    if (
        !confirm(
            'Are you sure you want to submit this loan application for review?',
        )
    ) {
        return;
    }

    router.patch(
        route('loans.submit', props.loan.id),
        {},
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head :title="`Loan ${loan.loan_no}`" />

    <div class="p-6">
        <div class="mx-auto max-w-6xl">
            <!-- Header -->
            <div
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="mb-2">
                        <Link
                            :href="route('loans.create')"
                            class="text-muted-foreground text-sm hover:underline"
                        >
                            ← New Loan Application
                        </Link>
                    </div>

                    <h1 class="text-2xl font-semibold">Loan Application</h1>

                    <p class="text-muted-foreground mt-1 text-sm">
                        {{ loan.loan_no }}
                    </p>
                </div>

                <!-- Buttons & Status -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Approve -->
                    <AlertDialog
                        v-if="
                            loan.status === 'draft' || loan.status === 'pending'
                        "
                    >
                        <AlertDialogTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                            >
                                Approve Loan
                            </button>
                        </AlertDialogTrigger>

                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    Approve Loan?
                                </AlertDialogTitle>

                                <AlertDialogDescription>
                                    Are you sure you want to approve loan
                                    <strong>{{ loan.loan_no }}</strong
                                    >? This will mark the loan as approved.
                                </AlertDialogDescription>
                            </AlertDialogHeader>

                            <AlertDialogFooter>
                                <AlertDialogCancel> Cancel </AlertDialogCancel>

                                <AlertDialogAction
                                    class="bg-green-600 text-white hover:bg-green-700"
                                    @click="
                                        router.patch(
                                            route('loans.approve', loan.id),
                                            {},
                                            {
                                                preserveScroll: true,
                                            },
                                        )
                                    "
                                >
                                    Approve Loan
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>

                    <!-- Reject -->
                    <AlertDialog
                        v-if="
                            loan.status === 'draft' || loan.status === 'pending'
                        "
                    >
                        <AlertDialogTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                            >
                                Reject Loan
                            </button>
                        </AlertDialogTrigger>

                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    Reject Loan?
                                </AlertDialogTitle>

                                <AlertDialogDescription>
                                    Are you sure you want to reject loan
                                    <strong>{{ loan.loan_no }}</strong
                                    >?
                                </AlertDialogDescription>
                            </AlertDialogHeader>

                            <AlertDialogFooter>
                                <AlertDialogCancel> Cancel </AlertDialogCancel>

                                <AlertDialogAction
                                    class="bg-red-600 text-white hover:bg-red-700"
                                    @click="
                                        router.patch(
                                            route('loans.reject', loan.id),
                                            {},
                                            {
                                                preserveScroll: true,
                                            },
                                        )
                                    "
                                >
                                    Reject Loan
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>

                    <!-- Release -->
                    <AlertDialog v-if="loan.status === 'approved'">
                        <AlertDialogTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                Release Loan
                            </button>
                        </AlertDialogTrigger>

                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    Release Loan?
                                </AlertDialogTitle>

                                <AlertDialogDescription>
                                    Are you sure you want to release loan
                                    <strong>{{ loan.loan_no }}</strong
                                    >? This will mark the loan as released.
                                </AlertDialogDescription>
                            </AlertDialogHeader>

                            <AlertDialogFooter>
                                <AlertDialogCancel> Cancel </AlertDialogCancel>

                                <AlertDialogAction
                                    class="bg-blue-600 text-white hover:bg-blue-700"
                                    @click="
                                        router.patch(
                                            route('loans.release', loan.id),
                                            {},
                                            {
                                                preserveScroll: true,
                                            },
                                        )
                                    "
                                >
                                    Release Loan
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>

                    <!-- Status -->
                    <span
                        class="inline-flex rounded-full px-3 py-1 text-sm font-medium"
                        :class="{
                            'bg-gray-100 text-gray-700':
                                loan.status === 'draft',

                            'bg-yellow-100 text-yellow-800':
                                loan.status === 'pending',

                            'bg-green-100 text-green-800':
                                loan.status === 'approved',

                            'bg-red-100 text-red-800':
                                loan.status === 'rejected',

                            'bg-blue-100 text-blue-800':
                                loan.status === 'released',
                        }"
                    >
                        {{ statusLabel }}
                    </span>
                </div>
            </div>

            <!-- Loan Summary -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Member -->
                <div class="bg-card rounded-lg border p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        Member Information
                    </h2>

                    <div class="space-y-3">
                        <div>
                            <p class="text-muted-foreground text-sm">
                                Member No.
                            </p>

                            <p class="font-medium">
                                {{ loan.member.member_no }}
                            </p>
                        </div>

                        <div>
                            <p class="text-muted-foreground text-sm">Name</p>

                            <p class="font-medium">
                                {{ memberName }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Loan Product -->
                <div class="bg-card rounded-lg border p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">Loan Product</h2>

                    <div class="space-y-3">
                        <div>
                            <p class="text-muted-foreground text-sm">Product</p>

                            <p class="font-medium">
                                {{ loan.loan_product.name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-muted-foreground text-sm">Code</p>

                            <p class="font-medium">
                                {{ loan.loan_product.code }}
                            </p>
                        </div>

                        <div>
                            <p class="text-muted-foreground text-sm">Term</p>

                            <p class="font-medium">
                                {{ loan.term_months }} months
                            </p>
                        </div>

                        <div>
                            <p class="text-muted-foreground text-sm">
                                Monthly Interest Rate
                            </p>

                            <p class="font-medium">
                                {{
                                    Number(loan.interest_rate_monthly).toFixed(
                                        2,
                                    )
                                }}%
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Application Details -->
                <div class="bg-card rounded-lg border p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        Application Details
                    </h2>

                    <div class="space-y-3">
                        <div>
                            <p class="text-muted-foreground text-sm">
                                Loan No.
                            </p>

                            <p class="font-medium">
                                {{ loan.loan_no }}
                            </p>
                        </div>

                        <div>
                            <p class="text-muted-foreground text-sm">
                                Date Created
                            </p>

                            <p class="font-medium">
                                {{ formatDate(loan.application_date) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-muted-foreground text-sm">Status</p>

                            <p class="font-medium">
                                {{ statusLabel }}
                            </p>
                        </div>

                        <div v-if="loan.release_date">
                            <p class="text-muted-foreground text-sm">
                                Release Date
                            </p>

                            <p class="font-medium">
                                {{ formatDate(loan.release_date) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="bg-card mt-6 rounded-lg border p-6 shadow-sm">
                <h2 class="mb-6 text-lg font-semibold">Financial Summary</h2>

                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">
                            Principal Amount
                        </span>

                        <span class="font-medium">
                            {{ formatCurrency(loan.principal_amount) }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">
                            CBU Retention
                        </span>

                        <span>
                            {{ formatCurrency(loan.cbu_retention) }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground"> Service Fee </span>

                        <span>
                            {{ formatCurrency(loan.service_fee) }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">
                            Insurance Fee
                        </span>

                        <span>
                            {{ formatCurrency(loan.insurance_premium) }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-muted-foreground">
                            Notarial Fee
                        </span>

                        <span>
                            {{ formatCurrency(loan.notarial_fee) }}
                        </span>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between text-xl font-semibold">
                            <span> Net Process </span>

                            <span>
                                {{ formatCurrency(loan.net_process) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Amortization Summary -->
            <div class="bg-card mt-6 rounded-lg border p-6 shadow-sm">
                <h2 class="mb-6 text-lg font-semibold">Amortization Summary</h2>

                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <p class="text-muted-foreground text-sm">
                            Monthly Amortization
                        </p>

                        <p class="mt-1 text-xl font-semibold">
                            {{ formatCurrency(loan.monthly_amortization ?? 0) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-sm">
                            Total Interest
                        </p>

                        <p class="mt-1 text-xl font-semibold">
                            {{ formatCurrency(totalInterest) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-sm">
                            Total Payable
                        </p>

                        <p class="mt-1 text-xl font-semibold">
                            {{ formatCurrency(totalPayable) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Outstanding Balance -->
            <div class="bg-card mt-6 rounded-lg border p-6 shadow-sm">
                <h2 class="mb-6 text-lg font-semibold">Outstanding Balance</h2>

                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <p class="text-muted-foreground text-sm">
                            Outstanding Principal
                        </p>

                        <p class="mt-1 text-lg font-semibold">
                            {{ formatCurrency(loan.outstanding_principal) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-sm">
                            Outstanding Interest
                        </p>

                        <p class="mt-1 text-lg font-semibold">
                            {{ formatCurrency(loan.outstanding_interest) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-sm">
                            Outstanding Penalty
                        </p>

                        <p class="mt-1 text-lg font-semibold">
                            {{ formatCurrency(loan.outstanding_penalty) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Amortization Schedule -->
            <div
                v-if="loan.status === 'released' && loan.amortizations?.length"
                class="bg-card mt-6 rounded-lg border p-6 shadow-sm"
            >
                <div class="mb-6">
                    <h2 class="text-lg font-semibold">Amortization Schedule</h2>

                    <p class="text-muted-foreground mt-1 text-sm">
                        Monthly repayment schedule for this loan.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="px-3 py-3 font-medium">#</th>
                                <th class="px-3 py-3 font-medium">Due Date</th>
                                <th class="px-3 py-3 text-right font-medium">
                                    Beginning Balance
                                </th>
                                <th class="px-3 py-3 text-right font-medium">
                                    Principal
                                </th>
                                <th class="px-3 py-3 text-right font-medium">
                                    Interest
                                </th>
                                <th class="px-3 py-3 text-right font-medium">
                                    Total Due
                                </th>
                                <th class="px-3 py-3 text-center font-medium">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="schedule in loan.amortizations"
                                :key="schedule.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-3 py-3">
                                    {{ schedule.installment_no }}
                                </td>
                                <td class="px-3 py-3">
                                    {{ formatDate(schedule.due_date) }}
                                </td>
                                <td class="px-3 py-3 text-right">
                                    {{
                                        formatCurrency(
                                            schedule.beginning_balance,
                                        )
                                    }}
                                </td>
                                <td class="px-3 py-3 text-right">
                                    {{
                                        formatCurrency(
                                            schedule.principal_amount,
                                        )
                                    }}
                                </td>
                                <td class="px-3 py-3 text-right">
                                    {{
                                        formatCurrency(schedule.interest_amount)
                                    }}
                                </td>
                                <td class="px-3 py-3 text-right font-medium">
                                    {{ formatCurrency(schedule.total_due) }}
                                </td>
                                <td class="px-3 py-3 text-center capitalize">
                                    {{ schedule.status }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Purpose / Remarks -->
            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div class="bg-card rounded-lg border p-6 shadow-sm">
                    <h2 class="mb-3 text-lg font-semibold">Purpose</h2>

                    <p class="text-sm whitespace-pre-wrap">
                        {{ loan.purpose || 'No purpose specified.' }}
                    </p>
                </div>

                <div class="bg-card rounded-lg border p-6 shadow-sm">
                    <h2 class="mb-3 text-lg font-semibold">Remarks</h2>

                    <p class="text-sm whitespace-pre-wrap">
                        {{ loan.remarks || 'No remarks.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
