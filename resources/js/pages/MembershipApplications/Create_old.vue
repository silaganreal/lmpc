<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Member {
    id: number;
    member_no: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    suffix: string | null;
    membership_type: string;
}

const props = defineProps<{
    members: Member[];
}>();

const form = useForm({
    member_id: '',
    date_of_application: new Date().toISOString().split('T')[0],
    membership_type: '',
    shares_subscribed: 1,
    amount_subscribed: 0,
    initial_paid_up: 0,
    recruiter_name: '',
    recruiter_mobile: '',
    remarks: '',
});

const memberName = (member: Member) => {
    return [
        member.first_name,
        member.middle_name,
        member.last_name,
        member.suffix,
    ]
        .filter(Boolean)
        .join(' ');
};

const submit = () => {
    form.post('/membership-applications');
};
</script>

<template>
    <Head title="New Membership Application" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div>
            <div class="mb-2">
                <Link
                    href="/membership-applications"
                    class="text-primary text-sm font-medium hover:underline"
                >
                    ← Back to Applications
                </Link>
            </div>

            <h1 class="text-primary text-2xl font-semibold tracking-tight">
                New Membership Application
            </h1>

            <p class="text-muted-foreground mt-1 text-sm">
                Create a new cooperative membership application.
            </p>
        </div>

        <!-- Form -->
        <form
            @submit.prevent="submit"
            class="bg-card overflow-hidden rounded-xl border shadow-sm"
        >
            <!-- Application Information -->
            <div class="bg-muted/30 border-b px-5 py-4">
                <h2 class="font-semibold">Application Information</h2>

                <p class="text-muted-foreground mt-1 text-sm">
                    Enter the basic information for this membership application.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Member -->
                <div class="sm:col-span-2 lg:col-span-2">
                    <label
                        for="member_id"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Member
                    </label>

                    <select
                        id="member_id"
                        v-model="form.member_id"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    >
                        <option value="">Select member</option>

                        <option
                            v-for="member in props.members"
                            :key="member.id"
                            :value="String(member.id)"
                        >
                            {{ member.member_no }} —
                            {{ memberName(member) }}
                        </option>
                    </select>

                    <p
                        v-if="form.errors.member_id"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.member_id }}
                    </p>
                </div>

                <!-- Date -->
                <div>
                    <label
                        for="date_of_application"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Date of Application
                    </label>

                    <input
                        id="date_of_application"
                        v-model="form.date_of_application"
                        type="date"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.date_of_application"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.date_of_application }}
                    </p>
                </div>

                <!-- Membership Type -->
                <div>
                    <label
                        for="membership_type"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Membership Type
                    </label>

                    <select
                        id="membership_type"
                        v-model="form.membership_type"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    >
                        <option value="">Select membership type</option>
                        <option value="regular">Regular</option>
                        <option value="associate">Associate</option>
                    </select>

                    <p
                        v-if="form.errors.membership_type"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.membership_type }}
                    </p>
                </div>
            </div>

            <!-- Share Capital -->
            <div class="bg-muted/30 border-y px-5 py-4">
                <h2 class="font-semibold">Share Capital</h2>

                <p class="text-muted-foreground mt-1 text-sm">
                    Record the applicant's subscribed shares and initial
                    payment.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Shares -->
                <div>
                    <label
                        for="shares_subscribed"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Shares Subscribed
                    </label>

                    <input
                        id="shares_subscribed"
                        v-model.number="form.shares_subscribed"
                        type="number"
                        min="1"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.shares_subscribed"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.shares_subscribed }}
                    </p>
                </div>

                <!-- Amount Subscribed -->
                <div>
                    <label
                        for="amount_subscribed"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Amount Subscribed
                    </label>

                    <div class="relative">
                        <span
                            class="text-muted-foreground absolute top-1/2 left-3 -translate-y-1/2 text-sm"
                        >
                            ₱
                        </span>

                        <input
                            id="amount_subscribed"
                            v-model.number="form.amount_subscribed"
                            type="number"
                            min="0"
                            step="0.01"
                            class="border-input bg-background focus:ring-primary w-full rounded-md border py-2 pr-3 pl-8 text-sm outline-none focus:ring-2"
                        />
                    </div>

                    <p
                        v-if="form.errors.amount_subscribed"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.amount_subscribed }}
                    </p>
                </div>

                <!-- Initial Paid Up -->
                <div>
                    <label
                        for="initial_paid_up"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Initial Paid-Up
                    </label>

                    <div class="relative">
                        <span
                            class="text-muted-foreground absolute top-1/2 left-3 -translate-y-1/2 text-sm"
                        >
                            ₱
                        </span>

                        <input
                            id="initial_paid_up"
                            v-model.number="form.initial_paid_up"
                            type="number"
                            min="0"
                            step="0.01"
                            class="border-input bg-background focus:ring-primary w-full rounded-md border py-2 pr-3 pl-8 text-sm outline-none focus:ring-2"
                        />
                    </div>

                    <p
                        v-if="form.errors.initial_paid_up"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.initial_paid_up }}
                    </p>
                </div>
            </div>

            <!-- Recruitment -->
            <div class="bg-muted/30 border-y px-5 py-4">
                <h2 class="font-semibold">Recruitment Information</h2>

                <p class="text-muted-foreground mt-1 text-sm">
                    Record the person who recruited or referred the applicant.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <!-- Recruiter Name -->
                <div>
                    <label
                        for="recruiter_name"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Recruiter Name
                    </label>

                    <input
                        id="recruiter_name"
                        v-model="form.recruiter_name"
                        type="text"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.recruiter_name"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.recruiter_name }}
                    </p>
                </div>

                <!-- Recruiter Mobile -->
                <div>
                    <label
                        for="recruiter_mobile"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Recruiter Mobile
                    </label>

                    <input
                        id="recruiter_mobile"
                        v-model="form.recruiter_mobile"
                        type="text"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.recruiter_mobile"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.recruiter_mobile }}
                    </p>
                </div>
            </div>

            <!-- Remarks -->
            <div class="bg-muted/30 border-y px-5 py-4">
                <h2 class="font-semibold">Remarks</h2>
            </div>

            <div class="p-5">
                <label for="remarks" class="mb-1.5 block text-sm font-medium">
                    Remarks
                </label>

                <textarea
                    id="remarks"
                    v-model="form.remarks"
                    rows="4"
                    class="border-input bg-background focus:ring-primary w-full resize-y rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    placeholder="Additional notes or remarks..."
                />

                <p
                    v-if="form.errors.remarks"
                    class="text-destructive mt-1 text-sm"
                >
                    {{ form.errors.remarks }}
                </p>
            </div>

            <!-- Actions -->
            <div
                class="bg-muted/20 flex flex-col-reverse gap-3 border-t px-5 py-4 sm:flex-row sm:justify-end"
            >
                <Link
                    href="/membership-applications"
                    class="border-input hover:bg-muted inline-flex items-center justify-center rounded-md border px-4 py-2 text-sm font-medium transition"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-primary text-primary-foreground inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating...' : 'Create Application' }}
                </button>
            </div>
        </form>
    </div>
</template>
