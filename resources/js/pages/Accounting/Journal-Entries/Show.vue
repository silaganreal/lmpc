<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import {
    ArrowLeft,
    BookOpen,
    CheckCircle2,
    XCircle,
    Pencil,
    RotateCcw,
    AlertTriangle,
} from 'lucide-vue-next';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import { ref } from 'vue';

type JournalStatus = 'draft' | 'posted' | 'reversed';

interface Account {
    id: number;
    account_code: string | null;
    account_name: string;
    account_type: string;
    normal_balance: 'debit' | 'credit';
}

interface JournalEntryLine {
    id: number;
    line_number: number;
    description: string | null;
    debit: string | number;
    credit: string | number;
    account: Account;
}

interface JournalEntry {
    id: number;
    entry_number: string;
    entry_date: string;
    reference_number: string | null;
    description: string;
    status: JournalStatus;
    created_at: string;
    updated_at: string;
    posted_at: string | null;
    lines: JournalEntryLine[];
    creator: { id: number; name: string } | null;
    poster: { id: number; name: string } | null;
    original_entry: {
        id: number;
        entry_number: string;
        entry_date: string;
    } | null;
    reversal_entry: {
        id: number;
        entry_number: string;
        entry_date: string;
    } | null;
}

const props = defineProps<{
    entry: JournalEntry;
    permissions: {
        canPost: boolean;
        canReverse: boolean;
    };
}>();

const showPostDialog = ref(false);
const isPosting = ref(false);

const showReverseDialog = ref(false);
const isReversing = ref(false);

const today = new Date();
const localToday = [
    today.getFullYear(),
    String(today.getMonth() + 1).padStart(2, '0'),
    String(today.getDate()).padStart(2, '0'),
].join('-');

const reversalDate = ref(localToday);
const reversalDescription = ref(
    `Reversal of journal entry ${props.entry.entry_number}`,
);

const totalDebit = props.entry.lines.reduce(
    (total, line) => total + Number(line.debit || 0),
    0,
);

const totalCredit = props.entry.lines.reduce(
    (total, line) => total + Number(line.credit || 0),
    0,
);

const isBalanced =
    Math.round(totalDebit * 100) === Math.round(totalCredit * 100);

const formatAmount = (amount: string | number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(Number(amount) || 0);
};

const formatDate = (date: string | null) => {
    if (!date) return '—';

    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: '2-digit',
    });
};

const formatDateTime = (date: string | null) => {
    if (!date) return '—';

    return new Date(date).toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const statusClass = (status: JournalStatus) => {
    return {
        draft: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        posted: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
        reversed:
            'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    }[status];
};

const accountLabel = (account: Account) => {
    return `${account.account_code ? account.account_code + ' - ' : ''}${account.account_name}`;
};

const postEntry = () => {
    isPosting.value = true;

    router.post(
        route('accounting.journal-entries.post', props.entry.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isPosting.value = false;
            },
            onSuccess: () => {
                showPostDialog.value = false;
            },
        },
    );
};

const reverseEntry = () => {
    isReversing.value = true;

    router.post(
        route('accounting.journal-entries.reverse', props.entry.id),
        {
            reversal_date: reversalDate.value,
            description: reversalDescription.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isReversing.value = false;
            },
            onSuccess: () => {
                showReverseDialog.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`Journal Entry ${entry.entry_number}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Link
                    :href="route('accounting.journal-entries.index')"
                    class="text-muted-foreground hover:text-foreground mb-3 inline-flex items-center gap-2 text-sm"
                >
                    <ArrowLeft class="size-4" />
                    Back to Journal Entries
                </Link>

                <h1 class="text-2xl font-semibold tracking-tight">
                    Journal Entry Details
                </h1>

                <p class="text-muted-foreground mt-1 text-sm">
                    Review the complete accounting transaction and its debit and
                    credit entries.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span
                    class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-medium capitalize"
                    :class="statusClass(entry.status)"
                >
                    {{ entry.status }}
                </span>

                <Link
                    v-if="entry.status === 'draft'"
                    :href="route('accounting.journal-entries.edit', entry.id)"
                    class="bg-primary text-primary-foreground inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium"
                >
                    <Pencil class="size-4" />
                    Edit Draft
                </Link>

                <button
                    v-if="entry.status === 'draft' && permissions.canPost"
                    type="button"
                    @click="showPostDialog = true"
                    class="inline-flex items-center gap-2 rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800"
                >
                    <CheckCircle2 class="size-4" />
                    Post Journal Entry
                </button>

                <button
                    v-if="
                        entry.status === 'posted' &&
                        permissions.canReverse &&
                        !entry.original_entry &&
                        !entry.reversal_entry
                    "
                    type="button"
                    @click="showReverseDialog = true"
                    class="inline-flex items-center gap-2 rounded-md border border-amber-600 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-950/30"
                >
                    <RotateCcw class="size-4" />
                    Reverse Journal Entry
                </button>
            </div>
        </div>

        <!-- Entry Information -->
        <div class="rounded-xl border">
            <div class="flex items-center gap-3 border-b p-4">
                <div class="bg-muted rounded-lg p-2">
                    <BookOpen class="size-5" />
                </div>

                <div>
                    <h2 class="font-semibold">Transaction Information</h2>
                    <p class="text-muted-foreground text-sm">
                        General journal record
                    </p>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-muted-foreground text-sm">
                        Journal Entry Number
                    </p>
                    <p class="mt-1 font-semibold">
                        {{ entry.entry_number }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Entry Date</p>
                    <p class="mt-1 font-medium">
                        {{ formatDate(entry.entry_date) }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">
                        Reference Number
                    </p>
                    <p class="mt-1 font-medium">
                        {{ entry.reference_number || '—' }}
                    </p>
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <p class="text-muted-foreground text-sm">
                        Transaction Description
                    </p>
                    <p class="mt-1 font-medium whitespace-pre-wrap">
                        {{ entry.description }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Journal Entry Lines -->
        <div class="overflow-hidden rounded-xl border">
            <div class="border-b p-4">
                <h2 class="font-semibold">Journal Entry Lines</h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    Detailed debit and credit account breakdown.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Account Code</th>
                            <th class="px-4 py-3 font-medium">Account Name</th>
                            <th class="px-4 py-3 font-medium">Description</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Debit
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Credit
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr
                            v-for="line in entry.lines"
                            :key="line.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                {{ line.line_number }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ line.account.account_code || '—' }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ line.account.account_name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ line.description || '—' }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{ formatAmount(line.debit) }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{ formatAmount(line.credit) }}
                            </td>
                        </tr>

                        <tr v-if="entry.lines.length === 0">
                            <td
                                colspan="6"
                                class="text-muted-foreground px-4 py-10 text-center"
                            >
                                No journal entry lines found.
                            </td>
                        </tr>
                    </tbody>

                    <tfoot class="bg-muted/50 font-semibold">
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-right">
                                Total
                            </td>

                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                {{ formatAmount(totalDebit) }}
                            </td>

                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                {{ formatAmount(totalCredit) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Balance Status -->
        <div class="rounded-xl border p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Debit / Credit Balance</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Total debits must equal total credits before posting.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <CheckCircle2
                        v-if="isBalanced"
                        class="size-5 text-green-600"
                    />
                    <XCircle v-else class="text-destructive size-5" />

                    <span
                        class="font-semibold"
                        :class="
                            isBalanced ? 'text-green-600' : 'text-destructive'
                        "
                    >
                        {{ isBalanced ? 'Balanced' : 'Not Balanced' }}
                    </span>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-muted-foreground text-sm">Total Debit</p>
                    <p class="mt-1 text-lg font-semibold">
                        {{ formatAmount(totalDebit) }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Total Credit</p>
                    <p class="mt-1 text-lg font-semibold">
                        {{ formatAmount(totalCredit) }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Difference</p>
                    <p class="mt-1 text-lg font-semibold">
                        {{ formatAmount(Math.abs(totalDebit - totalCredit)) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Audit Information -->
        <div class="rounded-xl border">
            <div class="border-b p-4">
                <h2 class="font-semibold">Audit Information</h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    Record preparation and posting details.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-muted-foreground text-sm">Prepared By</p>
                    <p class="mt-1 font-medium">
                        {{ entry.creator?.name ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Created At</p>
                    <p class="mt-1 font-medium">
                        {{ formatDateTime(entry.created_at) }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Last Updated</p>
                    <p class="mt-1 font-medium">
                        {{ formatDateTime(entry.updated_at) }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Posted By</p>
                    <p class="mt-1 font-medium">
                        {{ entry.poster?.name ?? 'Not yet posted' }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Posted At</p>
                    <p class="mt-1 font-medium">
                        {{ formatDateTime(entry.posted_at) }}
                    </p>
                </div>

                <div v-if="entry.original_entry">
                    <p class="text-muted-foreground text-sm">Reversal Of</p>
                    <Link
                        :href="
                            route(
                                'accounting.journal-entries.show',
                                entry.original_entry.id,
                            )
                        "
                        class="text-primary mt-1 inline-block font-medium hover:underline"
                    >
                        {{ entry.original_entry.entry_number }}
                    </Link>
                </div>

                <div v-if="entry.reversal_entry">
                    <p class="text-muted-foreground text-sm">Reversed By</p>
                    <Link
                        :href="
                            route(
                                'accounting.journal-entries.show',
                                entry.reversal_entry.id,
                            )
                        "
                        class="text-primary mt-1 inline-block font-medium hover:underline"
                    >
                        {{ entry.reversal_entry.entry_number }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="flex justify-end">
            <Link
                :href="route('accounting.journal-entries.index')"
                class="hover:bg-muted inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium"
            >
                <ArrowLeft class="size-4" />
                Back to Journal Entries
            </Link>
        </div>

        <!-- Post Confirmation Dialog -->
        <Dialog v-model:open="showPostDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Confirm Journal Entry Posting</DialogTitle>

                    <DialogDescription>
                        You are about to post journal entry
                        <strong>{{ entry.entry_number }}</strong
                        >.
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30"
                >
                    <div class="flex gap-3">
                        <icon class="text-amber-600" name="alert-triangle" />

                        <div>
                            <p
                                class="font-semibold text-amber-800 dark:text-amber-300"
                            >
                                Important: This action cannot be undone.
                            </p>

                            <p
                                class="mt-1 text-sm text-amber-700 dark:text-amber-400"
                            >
                                Once posted, this journal entry cannot be
                                edited. Any corrections must be made through a
                                reversal entry.
                            </p>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <button
                        type="button"
                        @click="showPostDialog = false"
                        :disabled="isPosting"
                        class="hover:bg-muted rounded-md border px-4 py-2 text-sm font-medium disabled:opacity-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="postEntry"
                        :disabled="isPosting"
                        class="inline-flex items-center gap-2 rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800 disabled:opacity-50"
                    >
                        <CheckCircle2 class="size-4" />
                        {{ isPosting ? 'Posting...' : 'Confirm Posting' }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Reversal Confirmation Dialog -->
        <Dialog v-model:open="showReverseDialog">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Confirm Journal Entry Reversal</DialogTitle>

                    <DialogDescription>
                        You are about to reverse journal entry
                        <strong>{{ entry.entry_number }}</strong
                        >. A new posted journal entry will be created.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <!-- Warning -->
                    <div
                        class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30"
                    >
                        <div class="flex gap-3">
                            <AlertTriangle
                                class="mt-0.5 size-5 shrink-0 text-amber-600"
                            />

                            <div>
                                <p
                                    class="font-semibold text-amber-800 dark:text-amber-300"
                                >
                                    Important: This action cannot be undone.
                                </p>

                                <p
                                    class="mt-1 text-sm text-amber-700 dark:text-amber-400"
                                >
                                    A reversal entry will be created with the
                                    debit and credit amounts swapped. The
                                    original posted entry will remain in the
                                    audit trail.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Reversal Date -->
                    <div class="space-y-2">
                        <label for="reversal-date" class="text-sm font-medium">
                            Reversal Date *
                        </label>

                        <input
                            id="reversal-date"
                            v-model="reversalDate"
                            type="date"
                            required
                            class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                        />
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label
                            for="reversal-description"
                            class="text-sm font-medium"
                        >
                            Reversal Description *
                        </label>

                        <textarea
                            id="reversal-description"
                            v-model="reversalDescription"
                            required
                            maxlength="5000"
                            rows="3"
                            class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Enter the reason for reversing this journal entry..."
                        />
                    </div>
                </div>

                <DialogFooter>
                    <button
                        type="button"
                        @click="showReverseDialog = false"
                        :disabled="isReversing"
                        class="hover:bg-muted rounded-md border px-4 py-2 text-sm font-medium disabled:opacity-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="reverseEntry"
                        :disabled="
                            isReversing ||
                            !reversalDate ||
                            !reversalDescription.trim()
                        "
                        class="inline-flex items-center gap-2 rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50"
                    >
                        <RotateCcw class="size-4" />
                        {{ isReversing ? 'Reversing...' : 'Confirm Reversal' }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
