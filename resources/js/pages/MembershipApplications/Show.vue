<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
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
    middle_name: string;
    last_name: string;
    suffix: string | null;
}

interface MembershipApplication {
    id: number;
    application_no: string;
    member_id: number | null;
    date_of_application: string;
    membership_type: 'regular' | 'associate';
    shares_subscribed: number;
    amount_subscribed: string | number;
    initial_paid_up: string | number;
    recruiter_name: string | null;
    recruiter_mobile: string | null;
    status:
        | 'draft'
        | 'submitted'
        | 'under_review'
        | 'approved'
        | 'rejected'
        | 'cancelled';
    remarks: string | null;
    member: Member | null;
}

interface Props {
    application: MembershipApplication;
}

defineProps<Props>();

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(date));
};

const formatCurrency = (amount: string | number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(amount));
};

const formatMembershipType = (type: string) => {
    return type === 'regular' ? 'Regular' : 'Associate';
};

const formatStatus = (status: string) => {
    return status
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

const statusClass = (status: string) => {
    switch (status) {
        case 'approved':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'submitted':
        case 'under_review':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'rejected':
        case 'cancelled':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        default:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    }
};

const submitApplication = (applicationId: number) => {
    router.patch(`/membership-applications/${applicationId}/submit`);
};
</script>

<template>
    <Head :title="`Application ${application.application_no}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Membership Application
                </h1>

                <p class="text-muted-foreground">
                    View membership application details.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    href="/membership-applications"
                    class="hover:bg-muted inline-flex items-center justify-center rounded-md border px-4 py-2 text-sm font-medium transition"
                >
                    Back to Applications
                </Link>

                <Link
                    v-if="application.status === 'draft'"
                    :href="`/membership-applications/${application.id}/edit`"
                    class="hover:bg-muted inline-flex items-center justify-center rounded-md border px-4 py-2 text-sm font-medium transition"
                >
                    Edit Application
                </Link>

                <AlertDialog v-if="application.status === 'draft'">
                    <AlertDialogTrigger as-child>
                        <button
                            type="button"
                            class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition"
                        >
                            Submit Application
                        </button>
                    </AlertDialogTrigger>

                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                Submit Membership Application?
                            </AlertDialogTitle>

                            <AlertDialogDescription>
                                This will submit the application for review.
                                Make sure all application information is
                                complete before continuing.
                            </AlertDialogDescription>
                        </AlertDialogHeader>

                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction
                                @click="submitApplication(application.id)"
                            >
                                Submit Application
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </div>

        <!-- Application Summary -->
        <div class="bg-card rounded-xl border p-6 shadow-sm">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <p class="text-muted-foreground text-sm">Application No.</p>

                    <h2 class="mt-1 text-2xl font-semibold">
                        {{ application.application_no }}
                    </h2>

                    <div v-if="application.member" class="mt-2">
                        <p class="font-medium">
                            {{ application.member.first_name }}
                            {{
                                application.member.middle_name
                                    ? ` ${application.member.middle_name}`
                                    : ''
                            }}
                            {{ application.member.last_name }}
                            {{
                                application.member.suffix
                                    ? ` ${application.member.suffix}`
                                    : ''
                            }}
                        </p>

                        <p class="text-muted-foreground text-sm">
                            {{ application.member.member_no }}
                        </p>
                    </div>

                    <p v-else class="text-muted-foreground mt-2 text-sm">
                        No member assigned
                    </p>
                </div>

                <span
                    class="inline-flex w-fit rounded-full px-3 py-1 text-sm font-medium"
                    :class="statusClass(application.status)"
                >
                    {{ formatStatus(application.status) }}
                </span>
            </div>
        </div>

        <!-- Application Information -->
        <div class="bg-card rounded-xl border p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Application Information</h2>

            <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-muted-foreground text-sm">
                        Date of Application
                    </p>

                    <p class="mt-1 font-medium">
                        {{ formatDate(application.date_of_application) }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Membership Type</p>

                    <p class="mt-1 font-medium">
                        {{ formatMembershipType(application.membership_type) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Share Capital -->
        <div class="bg-card rounded-xl border p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Share Capital</h2>

            <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-muted-foreground text-sm">
                        Shares Subscribed
                    </p>

                    <p class="mt-1 text-lg font-semibold">
                        {{ application.shares_subscribed }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">
                        Amount Subscribed
                    </p>

                    <p class="mt-1 text-lg font-semibold">
                        {{ formatCurrency(application.amount_subscribed) }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Initial Paid Up</p>

                    <p class="mt-1 text-lg font-semibold">
                        {{ formatCurrency(application.initial_paid_up) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Recruitment -->
        <div class="bg-card rounded-xl border p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Recruitment</h2>

            <div class="mt-4 grid gap-6 sm:grid-cols-2">
                <div>
                    <p class="text-muted-foreground text-sm">Recruiter Name</p>

                    <p class="mt-1 font-medium">
                        {{ application.recruiter_name || '-' }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">
                        Recruiter Mobile No.
                    </p>

                    <p class="mt-1 font-medium">
                        {{ application.recruiter_mobile || '-' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Remarks -->
        <div class="bg-card rounded-xl border p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Remarks</h2>

            <p class="text-muted-foreground mt-4 text-sm whitespace-pre-line">
                {{ application.remarks || 'No remarks provided.' }}
            </p>
        </div>
    </div>
</template>
