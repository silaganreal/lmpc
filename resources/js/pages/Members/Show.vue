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

interface Address {
    id: number;
    unit_room: string | null;
    floor_building: string | null;
    lot_block_phase: string | null;
    street_purok: string | null;
    subdivision: string | null;
    barangay: string | null;
    municipality: string | null;
    province: string | null;
    zip_code: string | null;
    address_type: string;
    is_primary: boolean;
}

interface FamilyMember {
    id: number;
    relationship: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    date_of_birth: string | null;
    sex: string | null;
    contact_number: string | null;
    is_lbmpc_member: boolean;
    is_beneficiary: boolean;
}

interface Education {
    id: number;
    education_level: string;
    school_name: string | null;
    course: string | null;
    year_graduated: number | null;
}

interface Employment {
    id: number;
    employer: string;
    branch_center: string | null;
    position: string | null;
    date_hired: string | null;
    is_current: boolean;
}

interface ShareTransaction {
    id: number;
    transaction_date: string;
    transaction_type: string;
    direction: string;
    reference_no?: string | null;
    description?: string | null;
    amount: number | string;
}

interface ShareAccount {
    shares_subscribed: number;
    total_subscribed_amount: number;
    paid_up_amount: number;
    status: string;
    transactions?: ShareTransaction[];
}

interface CbuTransaction {
    id: number;
    transaction_date: string;
    transaction_type: string;
    direction: string;
    reference_no?: string | null;
    description?: string | null;
    amount: number | string;
}

interface CbuAccount {
    current_balance: number | string;
    status: string;
    transactions?: CbuTransaction[];
}

interface SavingsTransaction {
    id: number;
    transaction_date: string;
    transaction_type: string;
    direction: string;
    reference_no?: string | null;
    description?: string | null;
    amount: number | string;
}

interface SavingsAccount {
    id: number;
    account_number: string;
    account_type: string;
    current_balance: number | string;
    status: string;
    opened_at?: string | null;
    closed_at?: string | null;
    transactions?: SavingsTransaction[];
}

interface Member {
    id: number;
    member_no: string;
    application_no: string | null;
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
    membership_type: string | null;
    date_joined: string | null;
    status: string;

    addresses: Address[];
    family_members: FamilyMember[];
    educations: Education[];
    employments: Employment[];
    share_account: ShareAccount | null;
    cbu_account: CbuAccount | null;
    savings_account?: SavingsAccount | null;
}

defineProps<{
    member: Member;
}>();

const deactivateMember = (memberId: number) => {
    router.delete(`/members/${memberId}`);
};

const reactivateMember = (memberId: number) => {
    router.patch(`/members/${memberId}/reactivate`);
};

const formatDate = (date: string | null) => {
    if (!date) {
        return '-';
    }

    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatAmount = (amount: number | string | null | undefined) => {
    if (amount === null || amount === undefined || amount === '') {
        return '0.00';
    }

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(amount));
};

const fullName = (member: Member) => {
    return [
        member.first_name,
        member.middle_name,
        member.last_name,
        member.suffix,
    ]
        .filter(Boolean)
        .join(' ');
};

const primaryAddress = (member: Member) => {
    return (
        member.addresses?.find((address) => address.is_primary) ??
        member.addresses?.[0] ??
        null
    );
};
</script>

<template>
    <Head :title="`Member - ${member.member_no}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="mb-2">
                    <Link
                        href="/members"
                        class="text-primary text-sm font-medium hover:underline"
                    >
                        ← Back to Members
                    </Link>
                </div>

                <h1 class="text-primary text-2xl font-semibold tracking-tight">
                    {{ fullName(member) }}
                </h1>

                <p class="text-muted-foreground mt-1 text-sm">
                    Member No. {{ member.member_no }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    :href="`/members/${member.id}/edit`"
                    class="bg-primary text-primary-foreground inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition hover:opacity-90"
                >
                    Edit Member
                </Link>

                <!-- Deactivate -->
                <AlertDialog v-if="member.status === 'active'">
                    <AlertDialogTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-100 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400 dark:hover:bg-red-950/50"
                        >
                            Deactivate Member
                        </button>
                    </AlertDialogTrigger>

                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                Deactivate Member?
                            </AlertDialogTitle>

                            <AlertDialogDescription>
                                Are you sure you want to deactivate
                                <strong>{{ fullName(member) }}</strong
                                >? The member's records will be retained, but
                                their membership status will be changed to
                                inactive.
                            </AlertDialogDescription>
                        </AlertDialogHeader>

                        <AlertDialogFooter>
                            <AlertDialogCancel> Cancel </AlertDialogCancel>

                            <AlertDialogAction
                                class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                @click="deactivateMember(member.id)"
                            >
                                Deactivate Member
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>

                <!-- Reactivate -->
                <AlertDialog v-else-if="member.status === 'inactive'">
                    <AlertDialogTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700 transition hover:bg-green-100 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-400 dark:hover:bg-green-950/50"
                        >
                            Reactivate Member
                        </button>
                    </AlertDialogTrigger>

                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                Reactivate Member?
                            </AlertDialogTitle>

                            <AlertDialogDescription>
                                Are you sure you want to reactivate
                                <strong>{{ fullName(member) }}</strong
                                >? The member's membership status will be
                                changed from inactive to active.
                            </AlertDialogDescription>
                        </AlertDialogHeader>

                        <AlertDialogFooter>
                            <AlertDialogCancel> Cancel </AlertDialogCancel>

                            <AlertDialogAction
                                class="bg-primary text-primary-foreground hover:bg-primary/90"
                                @click="reactivateMember(member.id)"
                            >
                                Reactivate Member
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </div>

        <!-- Basic Information -->
        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Personal Information -->
            <div
                class="bg-card overflow-hidden rounded-xl border shadow-sm lg:col-span-2"
            >
                <div class="bg-muted/30 border-b px-5 py-4">
                    <h2 class="font-semibold">Personal Information</h2>
                </div>

                <div class="grid gap-5 p-5 sm:grid-cols-2">
                    <div>
                        <p class="text-muted-foreground text-xs">Member No.</p>
                        <p class="mt-1 font-medium">
                            {{ member.member_no }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">
                            Membership Type
                        </p>
                        <p class="mt-1 font-medium capitalize">
                            {{ member.membership_type ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">Full Name</p>
                        <p class="mt-1 font-medium">
                            {{ fullName(member) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">Status</p>
                        <p class="mt-1 font-medium capitalize">
                            {{ member.status }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">
                            Date of Birth
                        </p>
                        <p class="mt-1">
                            {{ formatDate(member.date_of_birth) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">Sex</p>
                        <p class="mt-1 capitalize">
                            {{ member.sex ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">
                            Civil Status
                        </p>
                        <p class="mt-1 capitalize">
                            {{ member.civil_status ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">Date Joined</p>
                        <p class="mt-1">
                            {{ formatDate(member.date_joined) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">Nationality</p>
                        <p class="mt-1">
                            {{ member.nationality ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">TIN</p>
                        <p class="mt-1">
                            {{ member.tin ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="bg-card overflow-hidden rounded-xl border shadow-sm">
                <div class="bg-muted/30 border-b px-5 py-4">
                    <h2 class="font-semibold">Financial Summary</h2>
                </div>

                <div class="space-y-5 p-5">
                    <div>
                        <p class="text-muted-foreground text-xs">
                            Paid-Up Share Capital
                        </p>
                        <p class="text-primary mt-1 text-xl font-semibold">
                            {{
                                formatAmount(
                                    member.share_account?.paid_up_amount,
                                )
                            }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">CBU Balance</p>
                        <p class="text-primary mt-1 text-xl font-semibold">
                            {{
                                formatAmount(
                                    member.cbu_account?.current_balance,
                                )
                            }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">
                            Shares Subscribed
                        </p>
                        <p class="mt-1 text-lg font-medium">
                            {{ member.share_account?.shares_subscribed ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Share Capital Transaction -->
        <div class="bg-card rounded-lg border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">
                        Share Capital Transactions
                    </h2>

                    <p class="text-muted-foreground mt-1 text-sm">
                        Transaction history for this member's share capital.
                    </p>
                </div>

                <Link :href="`/members/${member.id}/share-capital/create`">
                    <Button> Record Payment </Button>
                </Link>
            </div>

            <div
                v-if="member.share_account?.transactions?.length"
                class="mt-6 overflow-x-auto"
            >
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="px-3 py-3 font-medium">Date</th>
                            <th class="px-3 py-3 font-medium">Type</th>
                            <th class="px-3 py-3 font-medium">Reference No.</th>
                            <th class="px-3 py-3 font-medium">Description</th>
                            <th class="px-3 py-3 text-right font-medium">
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="transaction in member.share_account
                                .transactions"
                            :key="transaction.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-3 py-3">
                                {{ transaction.transaction_date }}
                            </td>
                            <td class="px-3 py-3 capitalize">
                                {{ transaction.transaction_type }}
                            </td>
                            <td class="px-3 py-3">
                                {{ transaction.reference_no || '-' }}
                            </td>
                            <td class="px-3 py-3">
                                {{ transaction.description || '-' }}
                            </td>
                            <td class="px-3 py-3 text-right font-medium">
                                ₱{{
                                    Number(transaction.amount).toLocaleString(
                                        'en-PH',
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        },
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-else
                class="mt-6 rounded-md border border-dashed p-8 text-center"
            >
                <p class="text-muted-foreground text-sm">
                    No share capital transactions recorded yet.
                </p>
            </div>
        </div>

        <!-- CBU -->
        <div class="bg-card rounded-lg border p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">
                        Capital Build-Up (CBU)
                    </h2>

                    <p class="text-muted-foreground text-sm">
                        Member's current CBU balance and transaction history.
                    </p>
                </div>

                <Link :href="`/members/${member.id}/cbu/create`">
                    <Button> Record Contribution </Button>
                </Link>
            </div>

            <div class="mt-6">
                <p class="text-muted-foreground text-sm">Current CBU Balance</p>

                <p class="text-2xl font-semibold">
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

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="px-3 py-2 font-medium">Date</th>
                            <th class="px-3 py-2 font-medium">Transaction</th>
                            <th class="px-3 py-2 font-medium">Reference No.</th>
                            <th class="px-3 py-2 font-medium">Description</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="transaction in member.cbu_account
                                ?.transactions ?? []"
                            :key="transaction.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-3 py-2">
                                {{ transaction.transaction_date }}
                            </td>
                            <td class="px-3 py-2 capitalize">
                                {{
                                    transaction.transaction_type.replaceAll(
                                        '_',
                                        ' ',
                                    )
                                }}
                            </td>
                            <td class="px-3 py-2">
                                {{ transaction.reference_no || '-' }}
                            </td>
                            <td class="px-3 py-2">
                                {{ transaction.description || '-' }}
                            </td>
                            <td class="px-3 py-2 text-right font-medium">
                                <span
                                    :class="
                                        transaction.direction === 'credit'
                                            ? 'text-green-600'
                                            : 'text-red-600'
                                    "
                                >
                                    {{
                                        transaction.direction === 'credit'
                                            ? '+'
                                            : '-'
                                    }}₱{{
                                        Number(
                                            transaction.amount,
                                        ).toLocaleString('en-PH', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        })
                                    }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="!member.cbu_account?.transactions?.length">
                            <td
                                colspan="5"
                                class="text-muted-foreground px-3 py-6 text-center"
                            >
                                No CBU transaction found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Savings -->
        <div class="bg-card space-y-4 rounded-xl border p-6 shadow-sm">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-lg font-semibold">Savings</h2>

                    <p class="text-muted-foreground text-sm">
                        Regular savings account and transaction history.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        :href="`/members/${member.id}/savings/deposit`"
                        class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center rounded-md px-4 py-2 text-sm font-medium"
                    >
                        Record Deposit
                    </a>

                    <a
                        :href="`/members/${member.id}/savings/withdrawal`"
                        class="hover:bg-muted inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium"
                    >
                        Record Withdrawal
                    </a>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border p-4">
                    <p class="text-muted-foreground text-xs">Account Number</p>

                    <p class="mt-1 font-medium">
                        {{
                            member.savings_account?.account_number ??
                            'Not yet created'
                        }}
                    </p>
                </div>

                <div class="rounded-lg border p-4">
                    <p class="text-muted-foreground text-xs">Current Balance</p>

                    <p class="mt-1 text-xl font-bold">
                        {{
                            formatAmount(
                                member.savings_account?.current_balance,
                            )
                        }}
                    </p>
                </div>

                <div class="rounded-lg border p-4">
                    <p class="text-muted-foreground text-xs">Status</p>

                    <p class="mt-1 font-medium capitalize">
                        {{
                            member.savings_account?.status ?? 'Not yet created'
                        }}
                    </p>
                </div>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-semibold">Transaction History</h3>

                <div
                    v-if="member.savings_account?.transactions?.length"
                    class="overflow-x-auto rounded-lg border"
                >
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr class="border-b">
                                <th class="px-4 py-3 text-left font-medium">
                                    Date
                                </th>
                                <th class="px-4 py-3 text-left font-medium">
                                    Type
                                </th>
                                <th class="px-4 py-3 text-left font-medium">
                                    Reference
                                </th>
                                <th class="px-4 py-3 text-left font-medium">
                                    Description
                                </th>
                                <th class="px-4 py-3 text-right font-medium">
                                    Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="transaction in member.savings_account
                                    .transactions"
                                :key="transaction.id"
                                class="border-b last:border-b-0"
                            >
                                <td class="px-4 py-3">
                                    {{
                                        new Date(
                                            transaction.transaction_date,
                                        ).toLocaleDateString('en-PH')
                                    }}
                                </td>
                                <td class="px-4 py-3 capitalize">
                                    {{ transaction.transaction_type }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ transaction.reference_no ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ transaction.description ?? '-' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-medium"
                                    :class="
                                        transaction.direction === 'credit'
                                            ? 'text-green-600'
                                            : 'text-red-600'
                                    "
                                >
                                    {{
                                        transaction.direction === 'credit'
                                            ? '+ '
                                            : '- '
                                    }}{{ formatAmount(transaction.amount) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p
                    v-else
                    class="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm"
                >
                    No savings transactions recorded yet.
                </p>
            </div>
        </div>

        <!-- Contact & Address -->
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="bg-card overflow-hidden rounded-xl border shadow-sm">
                <div class="bg-muted/30 border-b px-5 py-4">
                    <h2 class="font-semibold">Contact Information</h2>
                </div>

                <div class="grid gap-5 p-5 sm:grid-cols-2">
                    <div>
                        <p class="text-muted-foreground text-xs">
                            Mobile Number
                        </p>
                        <p class="mt-1">
                            {{ member.mobile_number ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-xs">
                            Telephone Number
                        </p>
                        <p class="mt-1">
                            {{ member.telephone_number ?? '-' }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-muted-foreground text-xs">Email</p>
                        <p class="mt-1">
                            {{ member.email ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-card overflow-hidden rounded-xl border shadow-sm">
                <div class="bg-muted/30 border-b px-5 py-4">
                    <h2 class="font-semibold">Primary Address</h2>
                </div>

                <div class="p-5">
                    <template v-if="primaryAddress(member)">
                        <p>
                            {{
                                [
                                    primaryAddress(member)?.unit_room,
                                    primaryAddress(member)?.floor_building,
                                    primaryAddress(member)?.lot_block_phase,
                                    primaryAddress(member)?.street_purok,
                                    primaryAddress(member)?.subdivision,
                                ]
                                    .filter(Boolean)
                                    .join(', ')
                            }}
                        </p>

                        <p class="mt-1">
                            {{
                                [
                                    primaryAddress(member)?.barangay,
                                    primaryAddress(member)?.municipality,
                                    primaryAddress(member)?.province,
                                    primaryAddress(member)?.zip_code,
                                ]
                                    .filter(Boolean)
                                    .join(', ')
                            }}
                        </p>
                    </template>

                    <p v-else class="text-muted-foreground text-sm">
                        No address recorded.
                    </p>
                </div>
            </div>
        </div>

        <!-- Family Members -->
        <div class="bg-card overflow-hidden rounded-xl border shadow-sm">
            <div class="bg-muted/30 border-b px-5 py-4">
                <h2 class="font-semibold">Family Members</h2>
            </div>

            <div v-if="member.family_members?.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Relationship</th>
                            <th class="px-4 py-3 text-left">Birthday</th>
                            <th class="px-4 py-3 text-left">Beneficiary</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="family in member.family_members"
                            :key="family.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ family.last_name }},
                                {{ family.first_name }}
                                {{ family.middle_name ?? '' }}
                            </td>

                            <td class="px-4 py-3 capitalize">
                                {{ family.relationship }}
                            </td>

                            <td class="px-4 py-3">
                                {{ formatDate(family.date_of_birth) }}
                            </td>

                            <td class="px-4 py-3">
                                {{ family.is_beneficiary ? 'Yes' : 'No' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-muted-foreground p-5 text-sm">
                No family members recorded.
            </div>
        </div>

        <!-- Education -->
        <div class="bg-card overflow-hidden rounded-xl border shadow-sm">
            <div class="bg-muted/30 border-b px-5 py-4">
                <h2 class="font-semibold">Educational Background</h2>
            </div>

            <div v-if="member.educations?.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left">Level</th>
                            <th class="px-4 py-3 text-left">School</th>
                            <th class="px-4 py-3 text-left">Course</th>
                            <th class="px-4 py-3 text-left">Year Graduated</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="education in member.educations"
                            :key="education.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-4 py-3 capitalize">
                                {{ education.education_level }}
                            </td>

                            <td class="px-4 py-3">
                                {{ education.school_name ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ education.course ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ education.year_graduated ?? '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-muted-foreground p-5 text-sm">
                No educational background recorded.
            </div>
        </div>

        <!-- Employment -->
        <div class="bg-card overflow-hidden rounded-xl border shadow-sm">
            <div class="bg-muted/30 border-b px-5 py-4">
                <h2 class="font-semibold">Employment</h2>
            </div>

            <div v-if="member.employments?.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left">Employer</th>
                            <th class="px-4 py-3 text-left">Branch / Center</th>
                            <th class="px-4 py-3 text-left">Position</th>
                            <th class="px-4 py-3 text-left">Date Hired</th>
                            <th class="px-4 py-3 text-left">Current</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="employment in member.employments"
                            :key="employment.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ employment.employer }}
                            </td>

                            <td class="px-4 py-3">
                                {{ employment.branch_center ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ employment.position ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ formatDate(employment.date_hired) }}
                            </td>

                            <td class="px-4 py-3">
                                {{ employment.is_current ? 'Yes' : 'No' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-muted-foreground p-5 text-sm">
                No employment records.
            </div>
        </div>
    </div>
</template>
