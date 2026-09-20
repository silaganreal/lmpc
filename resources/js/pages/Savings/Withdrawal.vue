<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Wallet } from '@lucide/vue';

interface Member {
    id: number;
    member_no: string;
    first_name: string;
    middle_name?: string | null;
    last_name: string;
    suffix?: string | null;
}

interface SavingsAccount {
    id: number;
    account_number: string;
    account_type: string;
    current_balance: number | string;
    status: string;
}

const props = defineProps<{
    member: Member;
    savingsAccount: SavingsAccount | null;
}>();

const form = useForm({
    amount: '',
    transaction_date: new Date().toISOString().split('T')[0],
    reference_no: '',
    description: '',
});

const submit = () => {
    form.post(`/members/${props.member.id}/savings/withdrawal`);
};

const formatAmount = (amount: number | string | null | undefined) => {
    return Number(amount ?? 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const fullName = [
    props.member.first_name,
    props.member.middle_name,
    props.member.last_name,
    props.member.suffix,
]
    .filter(Boolean)
    .join(' ');

const currentBalance = Number(props.savingsAccount?.current_balance ?? 0);
</script>

<template>
    <Head title="Savings Withdrawal" />

    <div class="space-y-6 p-6">
        <div class="flex items-center gap-3">
            <Link
                :href="`/members/${member.id}`"
                class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 text-sm"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to Member
            </Link>
        </div>

        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Record Savings Withdrawal
            </h1>

            <p class="text-muted-foreground text-sm">
                Record a withdrawal for {{ fullName }}.
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="bg-card rounded-xl border p-6 shadow-sm lg:col-span-2">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="amount" class="text-sm font-medium">
                                Amount
                            </label>

                            <input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                :max="currentBalance"
                                class="bg-background w-full rounded-md border px-3 py-2 text-sm"
                                placeholder="0.01"
                            />

                            <p class="text-muted-foreground text-xs">
                                Available balance: ₱{{
                                    formatAmount(currentBalance)
                                }}
                            </p>

                            <p
                                v-if="form.errors.amount"
                                class="text-destructive text-sm"
                            >
                                {{ form.errors.amount }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label
                                for="transaction_date"
                                class="text-sm font-medium"
                            >
                                Transaction Date
                            </label>

                            <input
                                id="transaction_date"
                                v-model="form.transaction_date"
                                type="date"
                                class="bg-background w-full rounded-md border px-3 py-2 text-sm"
                            />

                            <p
                                v-if="form.errors.transaction_date"
                                class="text-destructive text-sm"
                            >
                                {{ form.errors.transaction_date }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="reference_no" class="text-sm font-medium">
                            Reference No.
                        </label>

                        <input
                            id="reference_no"
                            v-model="form.reference_no"
                            type="text"
                            maxlength="100"
                            class="bg-background w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Optional reference number"
                        />

                        <p
                            v-if="form.errors.reference_no"
                            class="text-destructive text-sm"
                        >
                            {{ form.errors.reference_no }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="description" class="text-sm font-medium">
                            Description
                        </label>

                        <textarea
                            id="description"
                            v-model="form.description"
                            maxlength="500"
                            rows="4"
                            class="bg-background w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Optional description"
                        />

                        <p
                            v-if="form.errors.description"
                            class="text-destructive text-sm"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <Link
                            :href="`/members/${member.id}`"
                            class="hover:bg-muted inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium"
                        >
                            Cancel
                        </Link>

                        <button
                            type="submit"
                            :disabled="form.processing || !savingsAccount"
                            class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Wallet class="h-4 w-4" />

                            {{
                                form.processing
                                    ? 'Saving...'
                                    : 'Record Withdrawal'
                            }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-card rounded-xl border p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Savings Account</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <p class="text-muted-foreground text-xs">Member</p>

                        <p class="font-medium">
                            {{ fullName }}
                        </p>

                        <p class="text-muted-foreground text-sm">
                            {{ member.member_no }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">
                            Account Number
                        </p>

                        <p class="font-medium">
                            {{
                                savingsAccount?.account_number ??
                                'Not yet created'
                            }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">
                            Current Balance
                        </p>

                        <p class="text-2xl font-bold">
                            ₱{{ formatAmount(currentBalance) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">Status</p>

                        <p class="font-medium capitalize">
                            {{ savingsAccount?.status ?? 'Not yet created' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
