<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';

interface Member {
    id: number;
    member_no: string;
    first_name: string;
    middle_name?: string | null;
    last_name: string;
    suffix?: string | null;
}

interface CbuAccount {
    current_balance: number | string;
    status: string;
}

interface Props {
    member: Member & {
        cbu_account?: CbuAccount | null;
    };
}

const props = defineProps<Props>();

const form = useForm({
    amount: '',
    transaction_date: new Date().toISOString().split('T')[0],
    reference_no: '',
    description: '',
});

const submit = () => {
    form.post(`/members/${props.member.id}/cbu`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Record CBU Certification" />

    <div class="space-y-6">
        <div>
            <Link
                :href="`/members/${member.id}`"
                class="text-muted-foreground text-sm hover:underline"
            >
                ← Back to Member
            </Link>

            <h1 class="mt-4 text-2xl font-semibold">Record CBU Contribution</h1>

            <p class="text-muted-foreground mt-1 text-sm">
                Record a contribution to the member's Capital Build-Up (CBU).
            </p>
        </div>

        <div class="bg-card rounded-lg border p-6">
            <h2 class="text-lg font-semibold">Member Information</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-muted-foreground text-sm">Member No.</p>

                    <p class="font-medium">
                        {{ member.member_no }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Name</p>

                    <p class="font-medium">
                        {{ member.first_name }}
                        {{ member.middle_name ? `${member.middle_name}` : '' }}
                        {{ member.last_name }}
                        {{ member.suffix ? `${member.suffix}` : '' }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">
                        Current CBU Balance
                    </p>

                    <p class="font-medium">
                        ₱{{
                            Number(
                                member.cbu_account?.current_balance ?? 0,
                            ).toLocaleString('en-PH', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })
                        }}
                    </p>
                </div>
            </div>
        </div>

        <form class="bg-card rounded-lg border p-6" @submit.prevent="submit">
            <h2 class="text-lg font-semibold">Contribution Details</h2>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="amount" class="text-sm font-medium">
                        Contribution Amount
                    </label>

                    <input
                        id="amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        placeholder="0.00"
                        class="bg-background w-full rounded-md border px-3 py-2 text-sm"
                        required
                    />

                    <p
                        v-if="form.errors.amount"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.amount }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label for="transaction_date" class="text-sm font-medium">
                        Transaction Date
                    </label>

                    <input
                        id="transaction_date"
                        v-model="form.transaction_date"
                        type="date"
                        class="bg-background w-full rounded-md border px-3 py-2 text-sm"
                        required
                    />

                    <p
                        v-if="form.errors.transaction_date"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.transaction_date }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label for="reference_no" class="text-sm font-medium">
                        Reference No.
                    </label>

                    <input
                        id="reference_no"
                        v-model="form.reference_no"
                        type="text"
                        placeholder="e.g. OR-000001"
                        class="bg-background w-full rounded-md border px-3 py-2 text-sm"
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

                    <input
                        id="description"
                        v-model="form.description"
                        type="text"
                        placeholder="Optional description"
                        class="bg-background w-full rounded-md border px-3 py-2 text-sm"
                    />

                    <p
                        v-if="form.errors.description"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <Link :href="`/members/${member.id}`">
                    <Button type="button" variant="outline"> Cancel </Button>
                </Link>

                <Button type="submit" :disabled="form.processing">
                    {{
                        form.processing ? 'Recording...' : 'Record Contribution'
                    }}
                </Button>
            </div>
        </form>
    </div>
</template>
