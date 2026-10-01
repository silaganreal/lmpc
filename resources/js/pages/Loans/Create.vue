<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

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
    eligibility_requirements?: string | null;
}

interface Props {
    members: Member[];
    loanProducts: LoanProduct[];
    selectedMember?: Member | null;
}

const props = defineProps<Props>();

const form = useForm({
    member_id: props.selectedMember?.id?.toString() ?? '',
    loan_product_id: '',
    principal_amount: '',
    term_months: '',
    purpose: '',
    remarks: '',
});

const selectedMember = computed(() => {
    return props.members.find(
        (member) => String(member.id) === String(form.member_id),
    );
});

const selectedProduct = computed(() => {
    return props.loanProducts.find(
        (product) => String(product.id) === String(form.loan_product_id),
    );
});

const principalAmount = computed(() => {
    return Number(form.principal_amount) || 0;
});

const termMonths = computed(() => {
    return Number(form.term_months) || 0;
});

const cbuRetention = computed(() => {
    return principalAmount.value * 0.03;
});

const serviceFee = computed(() => {
    return Math.min(principalAmount.value * 0.02, 1600);
});

const notarialFee = computed(() => {
    return principalAmount.value >= 30000 ? 150 : 0;
});

const insurancePremium = computed(() => {
    return 0;
});

const totalDeductions = computed(() => {
    return (
        cbuRetention.value +
        serviceFee.value +
        notarialFee.value +
        insurancePremium.value
    );
});

const netProcess = computed(() => {
    return Math.max(0, principalAmount.value - totalDeductions.value);
});

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(value);
};

const formatMemberName = (member: Member) => {
    return [member.last_name + ',', member.first_name, member.middle_name ?? '']
        .filter(Boolean)
        .join(' ');
};

const submit = () => {
    form.post(route('loans.store'));
};
</script>

<template>
    <Head title="New Loan Application" />

    <div class="bg-muted/20 min-h-full">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <div
                            class="text-muted-foreground mb-2 flex items-center gap-2 text-sm"
                        >
                            <a
                                :href="route('dashboard')"
                                class="hover:text-foreground transition-colors"
                            >
                                Dashboard
                            </a>

                            <span>/</span>

                            <span>Loans</span>

                            <span>/</span>

                            <span class="text-foreground">
                                New Application
                            </span>
                        </div>

                        <h1
                            class="text-2xl font-bold tracking-tight sm:text-3xl"
                        >
                            New Loan Application
                        </h1>

                        <p class="text-muted-foreground mt-1 text-sm">
                            Create and review a new loan application before
                            submission.
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Main Form -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Member & Product -->
                        <section class="bg-card rounded-xl border shadow-sm">
                            <div class="border-b px-6 py-5">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="bg-primary/10 text-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path d="M20 21a8 8 0 0 0-16 0" />
                                            <circle cx="12" cy="7" r="4" />
                                        </svg>
                                    </div>

                                    <div>
                                        <h2 class="font-semibold">
                                            Applicant Information
                                        </h2>

                                        <p
                                            class="text-muted-foreground text-sm"
                                        >
                                            Select the member and loan product
                                            for this application.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-6 p-6 md:grid-cols-2">
                                <!-- Member -->
                                <div>
                                    <label
                                        for="member_id"
                                        class="mb-2 block text-sm font-medium"
                                    >
                                        Member
                                        <span class="text-destructive">*</span>
                                    </label>

                                    <select
                                        id="member_id"
                                        v-model="form.member_id"
                                        class="bg-background focus:border-primary focus:ring-primary/20 w-full rounded-lg border px-3 py-2.5 text-sm transition outline-none focus:ring-2"
                                        :class="{
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                                form.errors.member_id,
                                        }"
                                    >
                                        <option value="">Select member</option>

                                        <option
                                            v-for="member in members"
                                            :key="member.id"
                                            :value="member.id"
                                        >
                                            {{ member.member_no }} -
                                            {{ formatMemberName(member) }}
                                        </option>
                                    </select>

                                    <p
                                        v-if="form.errors.member_id"
                                        class="mt-1.5 text-xs text-red-600"
                                    >
                                        {{ form.errors.member_id }}
                                    </p>

                                    <!-- Selected member preview -->
                                    <div
                                        v-if="selectedMember"
                                        class="bg-muted/30 mt-3 rounded-lg border p-3"
                                    >
                                        <p
                                            class="text-muted-foreground text-xs"
                                        >
                                            Selected Member
                                        </p>

                                        <p class="mt-1 text-sm font-medium">
                                            {{
                                                formatMemberName(selectedMember)
                                            }}
                                        </p>

                                        <p
                                            class="text-muted-foreground text-xs"
                                        >
                                            {{ selectedMember.member_no }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Loan Product -->
                                <div>
                                    <label
                                        for="loan_product_id"
                                        class="mb-2 block text-sm font-medium"
                                    >
                                        Loan Product
                                        <span class="text-destructive">*</span>
                                    </label>

                                    <select
                                        id="loan_product_id"
                                        v-model="form.loan_product_id"
                                        class="bg-background focus:border-primary focus:ring-primary/20 w-full rounded-lg border px-3 py-2.5 text-sm transition outline-none focus:ring-2"
                                        :class="{
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                                form.errors.loan_product_id,
                                        }"
                                    >
                                        <option value="">
                                            Select loan product
                                        </option>

                                        <option
                                            v-for="product in loanProducts"
                                            :key="product.id"
                                            :value="product.id"
                                        >
                                            {{ product.name }}
                                        </option>
                                    </select>

                                    <p
                                        v-if="form.errors.loan_product_id"
                                        class="mt-1.5 text-xs text-red-600"
                                    >
                                        {{ form.errors.loan_product_id }}
                                    </p>

                                    <div
                                        v-if="selectedProduct"
                                        class="bg-muted/30 mt-3 rounded-lg border p-3"
                                    >
                                        <p
                                            class="text-primary text-xs font-medium"
                                        >
                                            {{ selectedProduct.code }}
                                        </p>

                                        <p
                                            v-if="
                                                selectedProduct.eligibility_requirements
                                            "
                                            class="text-muted-foreground mt-1 text-xs leading-5"
                                        >
                                            {{
                                                selectedProduct.eligibility_requirements
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Loan Details -->
                        <section class="bg-card rounded-xl border shadow-sm">
                            <div class="border-b px-6 py-5">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="bg-primary/10 text-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path d="M12 1v22" />
                                            <path
                                                d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"
                                            />
                                        </svg>
                                    </div>

                                    <div>
                                        <h2 class="font-semibold">
                                            Loan Details
                                        </h2>

                                        <p
                                            class="text-muted-foreground text-sm"
                                        >
                                            Enter the requested loan amount and
                                            repayment period.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-6 p-6 md:grid-cols-2">
                                <!-- Principal -->
                                <div>
                                    <label
                                        for="principal_amount"
                                        class="mb-2 block text-sm font-medium"
                                    >
                                        Principal Amount
                                        <span class="text-destructive">*</span>
                                    </label>

                                    <div class="relative">
                                        <span
                                            class="text-muted-foreground pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm"
                                        >
                                            ₱
                                        </span>

                                        <input
                                            id="principal_amount"
                                            v-model="form.principal_amount"
                                            type="number"
                                            min="1"
                                            step="0.01"
                                            placeholder="0.00"
                                            class="bg-background focus:border-primary focus:ring-primary/20 w-full rounded-lg border py-2.5 pr-3 pl-8 text-sm transition outline-none focus:ring-2"
                                            :class="{
                                                'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                                    form.errors
                                                        .principal_amount,
                                            }"
                                        />
                                    </div>

                                    <p
                                        v-if="form.errors.principal_amount"
                                        class="mt-1.5 text-xs text-red-600"
                                    >
                                        {{ form.errors.principal_amount }}
                                    </p>
                                </div>

                                <!-- Term -->
                                <div>
                                    <label
                                        for="term_months"
                                        class="mb-2 block text-sm font-medium"
                                    >
                                        Repayment Term
                                        <span class="text-destructive">*</span>
                                    </label>

                                    <div class="relative">
                                        <input
                                            id="term_months"
                                            v-model="form.term_months"
                                            type="number"
                                            min="1"
                                            step="1"
                                            placeholder="12"
                                            class="bg-background focus:border-primary focus:ring-primary/20 w-full rounded-lg border px-3 py-2.5 pr-20 text-sm transition outline-none focus:ring-2"
                                            :class="{
                                                'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                                    form.errors.term_months,
                                            }"
                                        />

                                        <span
                                            class="text-muted-foreground pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs"
                                        >
                                            months
                                        </span>
                                    </div>

                                    <p
                                        v-if="form.errors.term_months"
                                        class="mt-1.5 text-xs text-red-600"
                                    >
                                        {{ form.errors.term_months }}
                                    </p>

                                    <p
                                        v-else-if="termMonths > 0"
                                        class="text-muted-foreground mt-1.5 text-xs"
                                    >
                                        {{ termMonths }} month repayment period
                                    </p>
                                </div>

                                <!-- Purpose -->
                                <div class="md:col-span-2">
                                    <label
                                        for="purpose"
                                        class="mb-2 block text-sm font-medium"
                                    >
                                        Purpose
                                    </label>

                                    <textarea
                                        id="purpose"
                                        v-model="form.purpose"
                                        rows="3"
                                        maxlength="1000"
                                        placeholder="Describe the purpose of this loan..."
                                        class="bg-background focus:border-primary focus:ring-primary/20 w-full resize-none rounded-lg border px-3 py-2.5 text-sm transition outline-none focus:ring-2"
                                        :class="{
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                                form.errors.purpose,
                                        }"
                                    />

                                    <div class="mt-1.5 flex justify-between">
                                        <p
                                            v-if="form.errors.purpose"
                                            class="text-xs text-red-600"
                                        >
                                            {{ form.errors.purpose }}
                                        </p>

                                        <span
                                            class="text-muted-foreground ml-auto text-xs"
                                        >
                                            {{ form.purpose.length }}/1000
                                        </span>
                                    </div>
                                </div>

                                <!-- Remarks -->
                                <div class="md:col-span-2">
                                    <label
                                        for="remarks"
                                        class="mb-2 block text-sm font-medium"
                                    >
                                        Remarks
                                    </label>

                                    <textarea
                                        id="remarks"
                                        v-model="form.remarks"
                                        rows="3"
                                        maxlength="2000"
                                        placeholder="Add any additional notes or remarks..."
                                        class="bg-background focus:border-primary focus:ring-primary/20 w-full resize-none rounded-lg border px-3 py-2.5 text-sm transition outline-none focus:ring-2"
                                        :class="{
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                                form.errors.remarks,
                                        }"
                                    />

                                    <div class="mt-1.5 flex justify-between">
                                        <p
                                            v-if="form.errors.remarks"
                                            class="text-xs text-red-600"
                                        >
                                            {{ form.errors.remarks }}
                                        </p>

                                        <span
                                            class="text-muted-foreground ml-auto text-xs"
                                        >
                                            {{ form.remarks.length }}/2000
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Financial Summary -->
                    <div class="lg:col-span-1">
                        <div
                            class="bg-card sticky top-6 rounded-xl border shadow-sm"
                        >
                            <div class="border-b px-6 py-5">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="bg-primary/10 text-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <rect
                                                x="3"
                                                y="4"
                                                width="18"
                                                height="16"
                                                rx="2"
                                            />
                                            <path d="M7 8h10" />
                                            <path d="M7 12h2" />
                                            <path d="M11 12h2" />
                                            <path d="M15 12h2" />
                                            <path d="M7 16h2" />
                                            <path d="M11 16h2" />
                                        </svg>
                                    </div>

                                    <div>
                                        <h2 class="font-semibold">
                                            Financial Summary
                                        </h2>

                                        <p
                                            class="text-muted-foreground text-sm"
                                        >
                                            Estimated loan deductions
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-5 p-6">
                                <!-- Principal highlight -->
                                <div class="bg-primary/5 rounded-lg p-4">
                                    <p
                                        class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                                    >
                                        Loan Amount
                                    </p>

                                    <p
                                        class="mt-1 text-2xl font-bold tracking-tight"
                                    >
                                        {{ formatCurrency(principalAmount) }}
                                    </p>

                                    <p
                                        v-if="selectedProduct"
                                        class="text-muted-foreground mt-1 text-xs"
                                    >
                                        {{ selectedProduct.name }}
                                    </p>
                                </div>

                                <!-- Deductions -->
                                <div>
                                    <p class="mb-3 text-sm font-semibold">
                                        Deductions
                                    </p>

                                    <div class="space-y-3 text-sm">
                                        <div
                                            class="flex items-center justify-between gap-4"
                                        >
                                            <span class="text-muted-foreground">
                                                CBU Retention
                                            </span>

                                            <span class="font-medium">
                                                {{
                                                    formatCurrency(cbuRetention)
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            class="flex items-center justify-between gap-4"
                                        >
                                            <span class="text-muted-foreground">
                                                Service Fee
                                            </span>

                                            <span class="font-medium">
                                                {{ formatCurrency(serviceFee) }}
                                            </span>
                                        </div>

                                        <div
                                            class="flex items-center justify-between gap-4"
                                        >
                                            <span class="text-muted-foreground">
                                                Notarial Fee
                                            </span>

                                            <span class="font-medium">
                                                {{
                                                    formatCurrency(notarialFee)
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            class="flex items-center justify-between gap-4"
                                        >
                                            <span class="text-muted-foreground">
                                                Insurance Premium
                                            </span>

                                            <span class="font-medium">
                                                {{
                                                    formatCurrency(
                                                        insurancePremium,
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <div class="border-t pt-3">
                                            <div
                                                class="flex items-center justify-between gap-4"
                                            >
                                                <span class="font-medium">
                                                    Total Deductions
                                                </span>

                                                <span class="font-semibold">
                                                    {{
                                                        formatCurrency(
                                                            totalDeductions,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Net Process -->
                                <div
                                    class="bg-background rounded-lg border p-4"
                                >
                                    <p
                                        class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                                    >
                                        Estimated Net Process
                                    </p>

                                    <p
                                        class="text-primary mt-1 text-2xl font-bold tracking-tight"
                                    >
                                        {{ formatCurrency(netProcess) }}
                                    </p>

                                    <p
                                        class="text-muted-foreground mt-1 text-xs"
                                    >
                                        Estimated amount after deductions
                                    </p>
                                </div>

                                <!-- Loan term -->
                                <div
                                    v-if="termMonths > 0"
                                    class="bg-muted/40 flex items-center justify-between rounded-lg px-4 py-3 text-sm"
                                >
                                    <span class="text-muted-foreground">
                                        Repayment Term
                                    </span>

                                    <span class="font-semibold">
                                        {{ termMonths }} months
                                    </span>
                                </div>

                                <!-- Notice -->
                                <div
                                    class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/50 dark:bg-amber-950/20"
                                >
                                    <div class="flex gap-2">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="mt-0.5 h-4 w-4 shrink-0 text-amber-600"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle cx="12" cy="12" r="10" />
                                            <path d="M12 8v4" />
                                            <path d="M12 16h.01" />
                                        </svg>

                                        <p
                                            class="text-xs leading-5 text-amber-800 dark:text-amber-200"
                                        >
                                            The figures shown here are
                                            estimates. Final loan deductions are
                                            calculated and validated by the
                                            system when the application is
                                            submitted.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div
                    class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:justify-end"
                >
                    <a
                        :href="route('dashboard')"
                        class="bg-background hover:bg-muted inline-flex items-center justify-center rounded-lg border px-5 py-2.5 text-sm font-medium transition"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium shadow-sm transition disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="form.processing"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="12" r="10" class="opacity-25" />
                            <path d="M4 12a8 8 0 0 1 8-8" class="opacity-75" />
                        </svg>

                        <span>
                            {{
                                form.processing
                                    ? 'Creating Application...'
                                    : 'Create Loan Application'
                            }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
