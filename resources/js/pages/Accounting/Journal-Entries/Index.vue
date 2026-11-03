<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed, ref } from 'vue';
import { Plus, Trash2 } from 'lucide-vue-next';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type JournalStatus = 'draft' | 'posted' | 'reversed';

interface ChartOfAccount {
    id: number;
    account_code: string | null;
    account_name: string;
    account_type: string;
    normal_balance: 'debit' | 'credit';
}

interface JournalLine {
    chart_of_account_id: number | null;
    description: string;
    debit: string;
    credit: string;
}

interface JournalEntry {
    id: number;
    entry_number: string;
    entry_date: string;
    reference_number: string | null;
    description: string;
    status: JournalStatus;
    total_debit: string | number | null;
    total_credit: string | number | null;
    creator: { id: number; name: string } | null;
    poster: { id: number; name: string } | null;
    posted_at: string | null;
    original_entry?: {
        id: number;
        entry_number: string;
    } | null;

    reversal_entry?: {
        id: number;
        entry_number: string;
    } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedEntries {
    data: JournalEntry[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

interface Filters {
    search: string;
    status: string;
    date_from: string;
    date_to: string;
}

const props = defineProps<{
    entries: PaginatedEntries;
    chartOfAccounts: ChartOfAccount[];
    filters: Filters;
    permissions: {
        canCreate: boolean;
    };
}>();

const localDate = () => {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const newLine = (): JournalLine => ({
    chart_of_account_id: null,
    description: '',
    debit: '',
    credit: '',
});

const showCreateDialog = ref(false);

const form = useForm({
    entry_date: localDate(),
    reference_number: '',
    description: '',
    lines: [newLine(), newLine()] as JournalLine[],
});

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

const totalDebit = computed(() =>
    form.lines.reduce((total, line) => total + (Number(line.debit) || 0), 0),
);

const totalCredit = computed(() =>
    form.lines.reduce((total, line) => total + (Number(line.credit) || 0), 0),
);

const difference = computed(
    () => Math.round((totalDebit.value - totalCredit.value) * 100) / 100,
);

const isBalanced = computed(
    () =>
        Math.round(totalDebit.value * 100) ===
        Math.round(totalCredit.value * 100),
);

const addLine = () => {
    form.lines.push(newLine());
};

const removeLine = (index: number) => {
    if (form.lines.length <= 2) return;

    form.lines.splice(index, 1);
};

const formatAmount = (amount: string | number | null) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(Number(amount) || 0);
};

const formatDate = (date: string) => {
    if (!date) return '—';

    return new Date(`${date.substring(0, 10)}T00:00:00`).toLocaleDateString(
        'en-PH',
        {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
        },
    );
};

const formatDateTime = (date: string | null) => {
    if (!date) return '—';

    const parsedDate = new Date(date);

    if (Number.isNaN(parsedDate.getTime())) return '—';

    return parsedDate.toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const statusLabel = (value: JournalStatus) => {
    return {
        draft: 'Draft',
        posted: 'Posted',
        reversed: 'Reversed',
    }[value];
};

const statusClass = (value: JournalStatus) => {
    return {
        draft: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        posted: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
        reversed:
            'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    }[value];
};

const accountLabel = (account: ChartOfAccount) => {
    return `${account.account_code ? account.account_code + ' - ' : ''}${account.account_name}`;
};

const applyFilters = () => {
    router.get(
        route('accounting.journal-entries.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    dateFrom.value = '';
    dateTo.value = '';

    router.get(
        route('accounting.journal-entries.index'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const openCreateDialog = () => {
    form.reset();
    form.clearErrors();

    form.entry_date = localDate();
    form.lines = [newLine(), newLine()];

    showCreateDialog.value = true;
};

const submitJournalEntry = () => {
    form.post(route('accounting.journal-entries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateDialog.value = false;
            form.reset();
            form.clearErrors();
        },
    });
};

const paginationLabel = (label: string) => {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
};
</script>

<template>
    <Head title="Journal Entries" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Journal Entries
            </h1>
            <p class="text-muted-foreground mt-1 text-sm">
                Record and manage the cooperative's accounting transactions.
            </p>
        </div>

        <!-- Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Total Entries</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ entries.total }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Current Page</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ entries.current_page }} / {{ entries.last_page }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Displayed Entries</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ entries.data.length }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Available Accounts</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ chartOfAccounts.length }}
                </p>
            </div>
        </div>

        <!-- Journal Entries Table -->
        <div class="overflow-hidden rounded-xl border">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b p-4"
            >
                <div>
                    <h2 class="font-semibold">Journal Entry Register</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        {{ entries.total }} journal entries recorded.
                    </p>
                </div>

                <button
                    v-if="permissions.canCreate"
                    type="button"
                    @click="openCreateDialog"
                    class="bg-primary text-primary-foreground inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium"
                >
                    <Plus class="size-4" />
                    New Journal Entry
                </button>
            </div>

            <!-- Filters -->
            <form
                class="flex flex-wrap items-end gap-3 border-b p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid min-w-[220px] flex-1 gap-1">
                    <label for="search" class="text-sm font-medium">
                        Search
                    </label>
                    <input
                        id="search"
                        v-model="search"
                        type="search"
                        placeholder="Entry number, reference, description..."
                        class="bg-background rounded-md border px-3 py-2 text-sm"
                    />
                </div>

                <div class="grid min-w-[140px] gap-1">
                    <label for="status" class="text-sm font-medium">
                        Status
                    </label>
                    <select
                        id="status"
                        v-model="status"
                        class="bg-background rounded-md border px-3 py-2 text-sm"
                    >
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="reversed">Reversed</option>
                    </select>
                </div>

                <div class="grid min-w-[150px] gap-1">
                    <label for="date_from" class="text-sm font-medium">
                        Date From
                    </label>
                    <input
                        id="date_from"
                        v-model="dateFrom"
                        type="date"
                        class="bg-background rounded-md border px-3 py-2 text-sm"
                    />
                </div>

                <div class="grid min-w-[150px] gap-1">
                    <label for="date_to" class="text-sm font-medium">
                        Date To
                    </label>
                    <input
                        id="date_to"
                        v-model="dateTo"
                        type="date"
                        class="bg-background rounded-md border px-3 py-2 text-sm"
                    />
                </div>

                <button
                    type="submit"
                    class="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm font-medium"
                >
                    Apply Filters
                </button>

                <button
                    type="button"
                    @click="resetFilters"
                    class="rounded-md border px-4 py-2 text-sm"
                >
                    Reset
                </button>
            </form>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Entry Number</th>
                            <th class="px-4 py-3 font-medium">Entry Date</th>
                            <th class="px-4 py-3 font-medium">Reference</th>
                            <th class="px-4 py-3 font-medium">Description</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Debit
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Credit
                            </th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap">
                                Related Entry
                            </th>
                            <th class="px-4 py-3 font-medium">Prepared By</th>
                            <th class="px-4 py-3 font-medium">Posted By</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap">
                                Posted At
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr
                            v-for="entry in entries.data"
                            :key="entry.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium whitespace-nowrap">
                                <Link
                                    :href="
                                        route(
                                            'accounting.journal-entries.show',
                                            entry.id,
                                        )
                                    "
                                    class="text-primary hover:underline"
                                >
                                    {{ entry.entry_number }}
                                </Link>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDate(entry.entry_date) }}
                            </td>

                            <td class="px-4 py-3">
                                {{ entry.reference_number || '—' }}
                            </td>

                            <td class="max-w-xs px-4 py-3">
                                <p class="truncate" :title="entry.description">
                                    {{ entry.description }}
                                </p>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{ formatAmount(entry.total_debit) }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{ formatAmount(entry.total_credit) }}
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="statusClass(entry.status)"
                                >
                                    {{ statusLabel(entry.status) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <Link
                                    v-if="entry.original_entry"
                                    :href="
                                        route(
                                            'accounting.journal-entries.show',
                                            entry.original_entry.id,
                                        )
                                    "
                                    class="text-primary font-medium hover:underline"
                                >
                                    {{ entry.original_entry.entry_number }}
                                </Link>

                                <Link
                                    v-else-if="entry.reversal_entry"
                                    :href="
                                        route(
                                            'accounting.journal-entries.show',
                                            entry.reversal_entry.id,
                                        )
                                    "
                                    class="text-primary font-medium hover:underline"
                                >
                                    {{ entry.reversal_entry.entry_number }}
                                </Link>

                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ entry.creator?.name ?? '—' }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ entry.poster?.name ?? '—' }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDateTime(entry.posted_at) }}
                            </td>
                        </tr>

                        <tr v-if="entries.data.length === 0">
                            <td
                                colspan="10"
                                class="text-muted-foreground px-4 py-10 text-center"
                            >
                                No journal entries found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-t p-4"
            >
                <span class="text-muted-foreground text-sm">
                    Showing {{ entries.data.length }} of
                    {{ entries.total }} entries
                </span>

                <div class="flex flex-wrap items-center gap-1">
                    <template
                        v-for="(link, index) in entries.links"
                        :key="index"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm font-medium transition-colors"
                            :class="
                                link.active
                                    ? 'bg-primary text-primary-foreground border-primary'
                                    : 'hover:bg-muted'
                            "
                        >
                            {{ paginationLabel(link.label) }}
                        </Link>

                        <span
                            v-else
                            class="text-muted-foreground flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm"
                        >
                            {{ paginationLabel(link.label) }}
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Create Journal Entry Dialog -->
        <Dialog v-model:open="showCreateDialog">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle>New Journal Entry</DialogTitle>
                    <DialogDescription>
                        Enter the transaction details and corresponding debit
                        and credit accounts. This entry will be saved as a
                        draft.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitJournalEntry" class="space-y-5">
                    <!-- Entry Information -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <label for="entry_date" class="text-sm font-medium">
                                Entry Date *
                            </label>
                            <input
                                id="entry_date"
                                v-model="form.entry_date"
                                type="date"
                                required
                                class="bg-background rounded-md border px-3 py-2 text-sm"
                            />
                            <p
                                v-if="form.errors.entry_date"
                                class="text-destructive text-xs"
                            >
                                {{ form.errors.entry_date }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="reference_number"
                                class="text-sm font-medium"
                            >
                                Reference Number
                            </label>
                            <input
                                id="reference_number"
                                v-model="form.reference_number"
                                type="text"
                                maxlength="100"
                                placeholder="Optional"
                                class="bg-background rounded-md border px-3 py-2 text-sm"
                            />
                            <p
                                v-if="form.errors.reference_number"
                                class="text-destructive text-xs"
                            >
                                {{ form.errors.reference_number }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <label for="description" class="text-sm font-medium">
                            Transaction Description *
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            maxlength="5000"
                            required
                            placeholder="Describe the transaction..."
                            class="bg-background rounded-md border px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors.description"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <!-- Journal Entry Lines -->
                    <div class="space-y-3">
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <div>
                                <h3 class="font-semibold">Entry Lines</h3>
                                <p class="text-muted-foreground text-xs">
                                    Add at least two lines. Enter either a debit
                                    or a credit for each line.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="addLine"
                                class="hover:bg-muted inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium"
                            >
                                <Plus class="size-4" />
                                Add Line
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-lg border">
                            <table class="w-full min-w-[850px] text-sm">
                                <thead class="bg-muted/50 text-left">
                                    <tr>
                                        <th class="w-12 px-3 py-3">#</th>
                                        <th class="px-3 py-3">Account *</th>
                                        <th class="px-3 py-3">
                                            Line Description
                                        </th>
                                        <th class="w-36 px-3 py-3 text-right">
                                            Debit
                                        </th>
                                        <th class="w-36 px-3 py-3 text-right">
                                            Credit
                                        </th>
                                        <th class="w-16 px-3 py-3"></th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y">
                                    <tr
                                        v-for="(line, index) in form.lines"
                                        :key="index"
                                    >
                                        <td class="px-3 py-3 text-center">
                                            {{ index + 1 }}
                                        </td>

                                        <td class="min-w-64 px-3 py-3">
                                            <select
                                                v-model="
                                                    line.chart_of_account_id
                                                "
                                                required
                                                class="bg-background w-full rounded-md border px-2 py-2 text-sm"
                                            >
                                                <option :value="null" disabled>
                                                    Select account
                                                </option>

                                                <option
                                                    v-for="account in chartOfAccounts"
                                                    :key="account.id"
                                                    :value="account.id"
                                                >
                                                    {{ accountLabel(account) }}
                                                </option>
                                            </select>

                                            <p
                                                v-if="
                                                    form.errors[
                                                        `lines.${index}.chart_of_account_id`
                                                    ]
                                                "
                                                class="text-destructive mt-1 text-xs"
                                            >
                                                {{
                                                    form.errors[
                                                        `lines.${index}.chart_of_account_id`
                                                    ]
                                                }}
                                            </p>
                                        </td>

                                        <td class="px-3 py-3">
                                            <input
                                                v-model="line.description"
                                                type="text"
                                                maxlength="255"
                                                placeholder="Optional"
                                                class="bg-background w-full rounded-md border px-2 py-2 text-sm"
                                            />

                                            <p
                                                v-if="
                                                    form.errors[
                                                        `lines.${index}.description`
                                                    ]
                                                "
                                                class="text-destructive mt-1 text-xs"
                                            >
                                                {{
                                                    form.errors[
                                                        `lines.${index}.description`
                                                    ]
                                                }}
                                            </p>
                                        </td>

                                        <td class="px-3 py-3">
                                            <input
                                                v-model="line.debit"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
                                                class="bg-background w-full rounded-md border px-2 py-2 text-right text-sm"
                                            />

                                            <p
                                                v-if="
                                                    form.errors[
                                                        `lines.${index}.debit`
                                                    ]
                                                "
                                                class="text-destructive mt-1 text-xs"
                                            >
                                                {{
                                                    form.errors[
                                                        `lines.${index}.debit`
                                                    ]
                                                }}
                                            </p>
                                        </td>

                                        <td class="px-3 py-3">
                                            <input
                                                v-model="line.credit"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
                                                class="bg-background w-full rounded-md border px-2 py-2 text-right text-sm"
                                            />

                                            <p
                                                v-if="
                                                    form.errors[
                                                        `lines.${index}.credit`
                                                    ]
                                                "
                                                class="text-destructive mt-1 text-xs"
                                            >
                                                {{
                                                    form.errors[
                                                        `lines.${index}.credit`
                                                    ]
                                                }}
                                            </p>
                                        </td>

                                        <td class="px-3 py-3 text-center">
                                            <button
                                                type="button"
                                                @click="removeLine(index)"
                                                :disabled="
                                                    form.lines.length <= 2
                                                "
                                                class="text-destructive hover:bg-muted rounded-md p-2 disabled:cursor-not-allowed disabled:opacity-30"
                                                title="Remove line"
                                            >
                                                <Trash2 class="size-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>

                                <tfoot class="bg-muted/50 font-semibold">
                                    <tr>
                                        <td
                                            colspan="3"
                                            class="px-3 py-3 text-right"
                                        >
                                            Total
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            {{ formatAmount(totalDebit) }}
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            {{ formatAmount(totalCredit) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <p
                            v-if="form.errors.lines"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.lines }}
                        </p>

                        <!-- Balance Indicator -->
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4"
                        >
                            <div>
                                <p class="text-sm font-semibold">
                                    Debit / Credit Difference
                                </p>
                                <p class="text-muted-foreground text-xs">
                                    Drafts may be unbalanced. They must balance
                                    before posting.
                                </p>
                            </div>

                            <div class="text-right">
                                <p
                                    class="text-lg font-semibold"
                                    :class="
                                        isBalanced
                                            ? 'text-green-600'
                                            : 'text-amber-600'
                                    "
                                >
                                    {{ formatAmount(Math.abs(difference)) }}
                                </p>
                                <p
                                    class="text-xs font-medium"
                                    :class="
                                        isBalanced
                                            ? 'text-green-600'
                                            : 'text-amber-600'
                                    "
                                >
                                    {{
                                        isBalanced ? 'Balanced' : 'Not Balanced'
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <DialogFooter>
                        <button
                            type="button"
                            @click="showCreateDialog = false"
                            class="rounded-md border px-4 py-2 text-sm"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="
                                form.processing || chartOfAccounts.length === 0
                            "
                            class="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                        >
                            {{
                                form.processing ? 'Saving...' : 'Save as Draft'
                            }}
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
