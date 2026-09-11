<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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
    date_of_application: string;
    membership_type: string;
    shares_subscribed: number;
    amount_subscribed: string | number;
    initial_paid_up: string | number;
    status: string;
    member: Member | null;
}

interface PaginatedApplications {
    data: MembershipApplication[];
    current_page: number;
    last_page: number;
    total: number;
}

defineProps<{
    applications: PaginatedApplications;
}>();

const getStatusClass = (status: string) => {
    switch (status.toLocaleLowerCase()) {
        case 'approved':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'submitted':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'under_review':
            return 'bg-purple-100 text-purple-700 dark:bg-blue-900/30 dark:text-purple-400';
        case 'draft':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        case 'rejected':
        case 'cancelled':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(date));
};

const formatAmount = (amount: string | number) => {
    return Number(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const membersName = (member: Member | null) => {
    if (!member) {
        return '-';
    }

    return [
        member.first_name,
        member.middle_name,
        member.last_name,
        member.suffix,
    ]
        .filter(Boolean)
        .join(' ');
};
</script>

<template>
    <Head title="Membership Applications" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Page Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Membership Applications
                </h1>

                <p class="text-muted-foreground">
                    Manage cooperative membership applications and their status.
                </p>
            </div>

            <Link
                href="/membership-applications/create"
                class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition"
            >
                New Application
            </Link>
        </div>

        <!-- Application Table -->
        <div class="bg-card rounded-xl border shadow-sm">
            <div class="border-b px-6 py-4">
                <h2 class="font-semibold">Applications</h2>

                <p class="text-muted-foreground text-sm">
                    {{ applications.total }}
                    {{
                        applications.total === 1
                            ? 'application'
                            : 'applications'
                    }}
                    found.
                </p>
            </div>

            <!-- Empty State -->
            <div
                v-if="applications.data.length === 0"
                class="flex flex-col items-center justify-center px-6 py-16 text-center"
            >
                <div
                    class="bg-primary/10 mb-4 flex size-12 items-center justify-center rounded-full"
                >
                    <span class="text-primary text-xl font-bold">MA</span>
                </div>

                <h3 class="text-lg font-semibold">
                    No membership applications
                </h3>

                <p class="text-muted-foreground mt-1 max-w-md text-sm">
                    There are currently no membership applications in the
                    system.
                </p>

                <Link
                    href="/membership-applications/create"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 mt-5 inline-flex items-center rounded-md px-4 py-2 text-sm font-medium"
                >
                    Create Application
                </Link>
            </div>

            <!-- Table -->
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/40 border-b">
                            <th class="px-6 py-2 text-left font-medium">
                                Application No.
                            </th>
                            <th class="px-6 py-3 text-left font-medium">
                                Applicant
                            </th>
                            <th class="px-6 py-3 text-left font-medium">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left font-medium">
                                Membership
                            </th>
                            <th class="px-6 py-3 text-left font-medium">
                                Shares
                            </th>
                            <th class="px-6 py-3 text-left font-medium">
                                Paid Up
                            </th>
                            <th class="px-6 py-3 text-left font-medium">
                                Status
                            </th>
                            <th class="px-6 py-3 text-right font-medium">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="application in applications.data"
                            :key="application.id"
                            class="hover:bg-muted/30 border-b last:border-0"
                        >
                            <td class="px-6 py-4 font-medium">
                                {{ application.application_no }}
                            </td>

                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-medium">
                                        {{ membersName(application.member) }}
                                    </p>
                                    <p
                                        v-if="application.member"
                                        class="text-muted-foreground text-xs"
                                    >
                                        {{ application.member?.member_no }}
                                    </p>
                                </div>
                            </td>

                            <td class="text-muted-foreground px-6 py-4">
                                {{
                                    formatDate(application.date_of_application)
                                }}
                            </td>

                            <td class="px-6 py-4 capitalize">
                                {{ application.membership_type }}
                            </td>

                            <td class="px-6 py-4 text-right">
                                {{ application.shares_subscribed }}
                            </td>

                            <td class="px-6 py-4 text-right font-medium">
                                ₱{{ formatAmount(application.initial_paid_up) }}
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                                    :class="getStatusClass(application.status)"
                                >
                                    {{ application.status.replace('_', ' ') }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <Link
                                    :href="`/membership-applications/${application.id}`"
                                    class="text-primary font-medium hover:underline"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="applications.last_page > 1"
                class="flex items-center justify-between border-t px-6 py-4"
            >
                <p class="text-muted-foreground text-sm">
                    Page {{ applications.current_page }} of
                    {{ applications.last_page }}
                </p>

                <div class="flex gap-2">
                    <Link
                        v-if="applications.current_page > 1"
                        :href="`/membership-applications?page=${applications.current_page - 1}`"
                        class="hover:bg-muted rounded-md border px-3 py-1.5 text-sm"
                    >
                        Previous
                    </Link>

                    <Link
                        v-if="
                            applications.current_page < applications.last_page
                        "
                        :href="`/membership-applications?page=${applications.current_page + 1}`"
                        class="hover:bg-muted rounded-md border px-3 py-1.5 text-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
