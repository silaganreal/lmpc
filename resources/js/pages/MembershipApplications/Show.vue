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
import Button from '@/components/ui/button/Button.vue';

interface Member {
    id: number;
    member_no: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    suffix: string | null;
}

interface MembershipApplication {
    id: number;
    application_no: string;
    member_id: number | null;

    // Applicant Information
    first_name: string;
    middle_name: string | null;
    last_name: string;
    suffix: string | null;
    date_of_birth: string | null;
    sex: string | null;
    civil_status: string | null;
    nationality: string | null;
    religion: string | null;
    place_of_birth: string | null;
    tin: string | null;
    mobile_number: string | null;
    telephone_number: string | null;
    email: string | null;
    residence_type: string | null;

    // Application Information
    date_of_application: string;
    membership_type: 'regular' | 'associate';

    // Share Capital
    shares_subscribed: number;
    amount_subscribed: string | number;
    initial_paid_up: string | number;

    // Recruitment
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

const props = defineProps<Props>();

const formatDate = (date: string | null) => {
    if (!date) {
        return '-';
    }

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

const formatValue = (value: string | null) => {
    return value || '-';
};

const fullName = () => {
    return [
        props.application.first_name,
        props.application.middle_name,
        props.application.last_name,
        props.application.suffix,
    ]
        .filter(Boolean)
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

const startReview = (applicationId: number) => {
    router.patch(`/membership-applications/${applicationId}/review`);
};

const approveApplication = (applicationId: number) => {
    router.patch(`/membership-applications/${applicationId}/approve`);
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

                <!-- Submit application -->
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

                <!-- Review application -->
                <AlertDialog v-if="application.status === 'submitted'">
                    <AlertDialogTrigger as-child>
                        <button
                            type="button"
                            class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition"
                        >
                            Start Review
                        </button>
                    </AlertDialogTrigger>

                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                Start Application Review?
                            </AlertDialogTitle>

                            <AlertDialogDescription>
                                This will move the membership application into
                                review and record you as the reviewer.
                            </AlertDialogDescription>
                        </AlertDialogHeader>

                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>

                            <AlertDialogAction
                                @click="startReview(application.id)"
                            >
                                Start Review
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>

                <!-- Approve application -->
                <AlertDialog v-if="application.status === 'under_review'">
                    <AlertDialogTrigger as-child>
                        <Button>Approve Application</Button>
                    </AlertDialogTrigger>

                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                Approve Membership Application?
                            </AlertDialogTitle>

                            <AlertDialogDescription>
                                This will approve the membership application and
                                create an active member from the applicant
                                information.
                            </AlertDialogDescription>
                        </AlertDialogHeader>

                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>

                            <AlertDialogAction
                                @click="approveApplication(application.id)"
                            >
                                Approve Application
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

                    <!-- Applicant name -->
                    <p class="mt-2 text-lg font-medium">
                        {{ fullName() }}
                    </p>

                    <!-- Member after approval -->
                    <div
                        v-if="application.member"
                        class="mt-2 flex flex-wrap items-center gap-2"
                    >
                        <span class="text-muted-foreground text-sm">
                            Member No.
                        </span>

                        <Link
                            :href="`/members/${application.member.id}`"
                            class="text-primary text-sm font-medium hover:underline"
                        >
                            {{ application.member.member_no }}
                        </Link>
                    </div>

                    <p v-else class="text-muted-foreground mt-1 text-sm">
                        Applicant — No member assigned yet
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

        <!-- Applicant Information -->
        <div class="bg-card rounded-xl border p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Applicant Information</h2>

            <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <!-- First Name -->
                <div>
                    <p class="text-muted-foreground text-sm">First Name</p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.first_name) }}
                    </p>
                </div>

                <!-- Middle Name -->
                <div>
                    <p class="text-muted-foreground text-sm">Middle Name</p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.middle_name) }}
                    </p>
                </div>

                <!-- Last Name -->
                <div>
                    <p class="text-muted-foreground text-sm">Last Name</p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.last_name) }}
                    </p>
                </div>

                <!-- Suffix -->
                <div>
                    <p class="text-muted-foreground text-sm">Suffix</p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.suffix) }}
                    </p>
                </div>

                <!-- Date of Birth -->
                <div>
                    <p class="text-muted-foreground text-sm">Date of Birth</p>

                    <p class="mt-1 font-medium">
                        {{ formatDate(application.date_of_birth) }}
                    </p>
                </div>

                <!-- Sex -->
                <div>
                    <p class="text-muted-foreground text-sm">Sex</p>

                    <p class="mt-1 font-medium capitalize">
                        {{ formatValue(application.sex) }}
                    </p>
                </div>

                <!-- Civil Status -->
                <div>
                    <p class="text-muted-foreground text-sm">Civil Status</p>

                    <p class="mt-1 font-medium capitalize">
                        {{ formatValue(application.civil_status) }}
                    </p>
                </div>

                <!-- Nationality -->
                <div>
                    <p class="text-muted-foreground text-sm">Nationality</p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.nationality) }}
                    </p>
                </div>

                <!-- Religion -->
                <div>
                    <p class="text-muted-foreground text-sm">Religion</p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.religion) }}
                    </p>
                </div>

                <!-- Place of Birth -->
                <div class="sm:col-span-2">
                    <p class="text-muted-foreground text-sm">Place of Birth</p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.place_of_birth) }}
                    </p>
                </div>

                <!-- TIN -->
                <div>
                    <p class="text-muted-foreground text-sm">TIN</p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.tin) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="bg-card rounded-xl border p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Contact Information</h2>

            <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Mobile -->
                <div>
                    <p class="text-muted-foreground text-sm">
                        Mobile / Cellphone
                    </p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.mobile_number) }}
                    </p>
                </div>

                <!-- Telephone -->
                <div>
                    <p class="text-muted-foreground text-sm">Telephone</p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.telephone_number) }}
                    </p>
                </div>

                <!-- Email -->
                <div>
                    <p class="text-muted-foreground text-sm">Email</p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.email) }}
                    </p>
                </div>

                <!-- Residence -->
                <div>
                    <p class="text-muted-foreground text-sm">
                        Type of Residence
                    </p>

                    <p class="mt-1 font-medium">
                        {{ formatValue(application.residence_type) }}
                    </p>
                </div>
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
