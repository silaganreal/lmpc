<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref } from 'vue';
import { Check, ChevronsUpDown, Pencil, Power } from 'lucide-vue-next';
import { computed } from 'vue';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';

import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

interface Account {
    id: number;
    account_code: string | null;
    account_name: string;
    account_type: 'asset' | 'liability' | 'equity' | 'income' | 'expense';
    normal_balance: 'debit' | 'credit';
    parent: {
        id: number;
        account_name: string;
    } | null;
    is_posting_allowed: boolean;
    is_active: boolean;
    description: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedAccounts {
    data: Account[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

interface Filters {
    search: string;
    account_type: string;
    status: string;
}

interface ParentAccount {
    id: number;
    account_name: string;
}

interface HierarchyAccount {
    id: number;
    account_name: string;
    account_type: Account['account_type'];
    parent_id: number | null;
}

const parentAccountOpen = ref(false);

const showCreateDialog = ref(false);

const form = useForm({
    account_code: '',
    account_name: '',
    account_type: 'asset',
    normal_balance: 'debit',
    parent_id: null as number | null,
    is_posting_allowed: true,
    description: '',
});

const submitAccount = () => {
    form.post(route('accounting.chart-of-accounts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateDialog.value = false;
            form.reset();
            form.clearErrors();
        },
    });
};

// edit account
const showEditDialog = ref(false);
const editingAccountId = ref<number | null>(null);

const editForm = useForm({
    account_code: '',
    account_name: '',
    account_type: 'asset',
    normal_balance: 'debit',
    parent_id: null as number | null,
    is_posting_allowed: true,
    description: '',
});

const openEditDialog = (account: Account) => {
    editingAccountId.value = account.id;

    editForm.account_code = account.account_code ?? '';
    editForm.account_name = account.account_name;
    editForm.account_type = account.account_type;
    editForm.normal_balance = account.normal_balance;
    editForm.parent_id = account.parent?.id ?? null;
    editForm.is_posting_allowed = account.is_posting_allowed;
    editForm.description = account.description ?? '';

    showEditDialog.value = true;
};

const editingAccount = computed(
    () =>
        props.accounts.data.find(
            (account) => account.id === editingAccountId.value,
        ) ?? null,
);

const getDescendantIds = (accountId: number): number[] => {
    const descendants: number[] = [];

    const findChildren = (parentId: number) => {
        const children = props.allAccounts.filter(
            (account) => account.parent_id === parentId,
        );

        for (const child of children) {
            descendants.push(child.id);
            findChildren(child.id);
        }
    };

    findChildren(accountId);

    return descendants;
};

const getEligibleParentAccounts = (account: Account) => {
    const excludedIds = [account.id, ...getDescendantIds(account.id)];

    return props.allAccounts.filter(
        (candidate) =>
            candidate.account_type === editForm.account_type &&
            !excludedIds.includes(candidate.id),
    );
};

const submitEditAccount = () => {
    if (!editingAccountId.value) return;

    editForm.put(
        route('accounting.chart-of-accounts.update', editingAccountId.value),
        {
            preserveScroll: true,
            onSuccess: () => {
                showEditDialog.value = false;
                editingAccountId.value = null;
                editForm.reset();
                editForm.clearErrors();
            },
        },
    );
};

// active/deactivate account status
const showStatusDialog = ref(false);
const selectedStatusAccount = ref<Account | null>(null);
const statusError = ref<string | null>(null);

const statusForm = useForm({
    is_active: true,
});

const openStatusDialog = (account: Account) => {
    statusError.value = null;
    statusForm.clearErrors();

    selectedStatusAccount.value = account;
    statusForm.is_active = !account.is_active;
    showStatusDialog.value = true;
};

const submitStatusUpdate = () => {
    if (!selectedStatusAccount.value) return;

    statusError.value = null;

    router.patch(
        route(
            'accounting.chart-of-accounts.update-status',
            selectedStatusAccount.value.id,
        ),
        {
            is_active: statusForm.is_active,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                statusError.value = null;
                showStatusDialog.value = false;
                selectedStatusAccount.value = null;
                statusForm.reset();
            },

            onError: (errors) => {
                statusError.value = errors.status
                    ? String(errors.status)
                    : null;
            },
        },
    );
};

const props = defineProps<{
    accounts: PaginatedAccounts;
    parentAccounts: ParentAccount[];
    allAccounts: HierarchyAccount[];
    filters: Filters;
}>();

const search = ref(props.filters.search);
const accountType = ref(props.filters.account_type);
const status = ref(props.filters.status);

const applyFilters = () => {
    router.get(
        route('accounting.chart-of-accounts.index'),
        {
            search: search.value || undefined,
            account_type: accountType.value || undefined,
            status: status.value || undefined,
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
    accountType.value = '';
    status.value = 'active';

    router.get(
        route('accounting.chart-of-accounts.index'),
        { status: 'active' },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const accountTypeLabel = (type: string) =>
    ({
        asset: 'Asset',
        liability: 'Liability',
        equity: 'Equity',
        income: 'Income',
        expense: 'Expense',
    })[type] ?? type;

const accountTypeClass = (type: string) =>
    ({
        asset: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
        liability:
            'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        equity: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200',
        income: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
        expense: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
    })[type] ?? 'bg-muted text-muted-foreground';

const paginationLabel = (label: string) =>
    label.replace('&laquo;', '«').replace('&raquo;', '»');
</script>

<template>
    <Head title="Chart of Accounts" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Chart of Accounts
            </h1>
            <p class="text-muted-foreground mt-1 text-sm">
                Manage the cooperative's accounting accounts and
                classifications.
            </p>
        </div>

        <!-- Summary -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Total Accounts</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ accounts.total }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Current Page</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ accounts.current_page }} / {{ accounts.last_page }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Account Types</p>
                <p class="mt-2 text-2xl font-semibold">5</p>
            </div>

            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Displayed Accounts</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ accounts.data.length }}
                </p>
            </div>
        </div>

        <!-- Accounts Table -->
        <div class="overflow-hidden rounded-xl border">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b p-4"
            >
                <div>
                    <h2 class="font-semibold">Account Register</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        {{ accounts.total }} accounts registered in the Chart of
                        Accounts.
                    </p>
                </div>

                <button
                    type="button"
                    @click="showCreateDialog = true"
                    class="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm font-medium"
                >
                    + Add Account
                </button>
            </div>

            <!-- Filters -->
            <form
                class="flex flex-wrap items-end gap-3 border-b p-4"
                @submit.prevent="applyFilters"
            >
                <div class="grid min-w-[220px] flex-1 gap-1">
                    <label for="search" class="text-sm font-medium">
                        Search Account
                    </label>
                    <input
                        id="search"
                        v-model="search"
                        type="search"
                        placeholder="Account name or code..."
                        class="bg-background rounded-md border px-3 py-2 text-sm"
                    />
                </div>

                <div class="grid min-w-[160px] gap-1">
                    <label for="account_type" class="text-sm font-medium">
                        Account Type
                    </label>
                    <select
                        id="account_type"
                        v-model="accountType"
                        class="bg-background rounded-md border px-3 py-2 text-sm"
                    >
                        <option value="">All Types</option>
                        <option value="asset">Asset</option>
                        <option value="liability">Liability</option>
                        <option value="equity">Equity</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
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
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
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
                            <th class="px-4 py-3 font-medium">Account Code</th>
                            <th class="px-4 py-3 font-medium">Account Name</th>
                            <th class="px-4 py-3 font-medium">Account Type</th>
                            <th class="px-4 py-3 font-medium">
                                Normal Balance
                            </th>
                            <th class="px-4 py-3 font-medium">
                                Parent Account
                            </th>
                            <th class="px-4 py-3 font-medium">Posting</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr
                            v-for="account in accounts.data"
                            :key="account.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ account.account_code || '—' }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ account.account_name }}
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        accountTypeClass(account.account_type)
                                    "
                                >
                                    {{ accountTypeLabel(account.account_type) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 capitalize">
                                {{ account.normal_balance }}
                            </td>

                            <td class="px-4 py-3">
                                {{ account.parent?.account_name ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{
                                    account.is_posting_allowed
                                        ? 'Allowed'
                                        : 'Not Allowed'
                                }}
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        account.is_active
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'
                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                    "
                                >
                                    {{
                                        account.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    @click="openEditDialog(account)"
                                    class="hover:bg-muted inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium"
                                >
                                    <Pencil class="size-4" />
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    @click="openStatusDialog(account)"
                                    class="hover:bg-muted inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium"
                                >
                                    <Power class="size-4" />
                                    {{
                                        account.is_active
                                            ? 'Deactivate'
                                            : 'Activate'
                                    }}
                                </button>
                            </td>
                        </tr>

                        <tr v-if="accounts.data.length === 0">
                            <td
                                colspan="8"
                                class="text-muted-foreground px-4 py-10 text-center"
                            >
                                No accounts found.
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
                    Showing {{ accounts.data.length }} of
                    {{ accounts.total }} accounts
                </span>

                <div class="flex flex-wrap items-center gap-1">
                    <template
                        v-for="(link, index) in accounts.links"
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
                            class="text-muted-foreground flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm opacity-50"
                        >
                            {{ paginationLabel(link.label) }}
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Create Account Dialog -->
        <Dialog v-model:open="showCreateDialog">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Add Account</DialogTitle>
                    <DialogDescription>
                        Register a new account in the Chart of Accounts.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitAccount" class="space-y-4">
                    <!-- Account Code -->
                    <div class="grid gap-2">
                        <label for="account_code" class="text-sm font-medium">
                            Account Code
                        </label>

                        <input
                            id="account_code"
                            v-model="form.account_code"
                            type="text"
                            maxlength="50"
                            placeholder="Leave blank to auto-generate"
                            class="bg-background rounded-md border px-3 py-2 text-sm"
                        />

                        <p class="text-muted-foreground text-xs">
                            Leave this field blank to automatically generate an
                            account code based on the selected account type. You
                            may also enter a custom account code.
                        </p>

                        <p
                            v-if="form.errors.account_code"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.account_code }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <label for="account_name" class="text-sm font-medium">
                            Account Name *
                        </label>
                        <input
                            id="account_name"
                            v-model="form.account_name"
                            type="text"
                            required
                            maxlength="150"
                            class="bg-background rounded-md border px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors.account_name"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.account_name }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <label
                                for="account_type"
                                class="text-sm font-medium"
                            >
                                Account Type *
                            </label>
                            <select
                                id="account_type"
                                v-model="form.account_type"
                                required
                                class="bg-background rounded-md border px-3 py-2 text-sm"
                            >
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                            <p
                                v-if="form.errors.account_type"
                                class="text-destructive text-xs"
                            >
                                {{ form.errors.account_type }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="normal_balance"
                                class="text-sm font-medium"
                            >
                                Normal Balance *
                            </label>
                            <select
                                id="normal_balance"
                                v-model="form.normal_balance"
                                required
                                class="bg-background rounded-md border px-3 py-2 text-sm"
                            >
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                            <p
                                v-if="form.errors.normal_balance"
                                class="text-destructive text-xs"
                            >
                                {{ form.errors.normal_balance }}
                            </p>
                        </div>
                    </div>

                    <!-- Parent Account -->
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Parent Account</label
                        >

                        <Popover v-model:open="parentAccountOpen">
                            <PopoverTrigger as-child>
                                <button
                                    type="button"
                                    role="combobox"
                                    :aria-expanded="parentAccountOpen"
                                    class="bg-background flex w-full items-center justify-between gap-2 rounded-md border px-3 py-2 text-left text-sm"
                                >
                                    <span class="min-w-0 flex-1 truncate">
                                        {{
                                            parentAccounts.find(
                                                (account) =>
                                                    account.id ===
                                                    form.parent_id,
                                            )?.account_name ??
                                            'No Parent Account'
                                        }}
                                    </span>

                                    <ChevronsUpDown
                                        class="size-4 shrink-0 opacity-50"
                                    />
                                </button>
                            </PopoverTrigger>

                            <PopoverContent
                                class="w-[var(--reka-popover-trigger-width)] p-0"
                                align="start"
                            >
                                <Command>
                                    <CommandInput
                                        placeholder="Search parent account..."
                                    />

                                    <CommandList class="max-h-60">
                                        <CommandEmpty>
                                            No account found.
                                        </CommandEmpty>

                                        <CommandGroup>
                                            <CommandItem
                                                value="no-parent-account"
                                                @select="
                                                    () => {
                                                        form.parent_id = null;
                                                        parentAccountOpen = false;
                                                    }
                                                "
                                            >
                                                <Check
                                                    class="mr-2 size-4"
                                                    :class="
                                                        form.parent_id === null
                                                            ? 'opacity-100'
                                                            : 'opacity-0'
                                                    "
                                                />
                                                No Parent Account
                                            </CommandItem>

                                            <CommandItem
                                                v-for="account in parentAccounts"
                                                :key="account.id"
                                                :value="account.account_name"
                                                @select="
                                                    () => {
                                                        form.parent_id =
                                                            account.id;
                                                        parentAccountOpen = false;
                                                    }
                                                "
                                            >
                                                <Check
                                                    class="mr-2 size-4"
                                                    :class="
                                                        form.parent_id ===
                                                        account.id
                                                            ? 'opacity-100'
                                                            : 'opacity-0'
                                                    "
                                                />

                                                <span class="truncate">
                                                    {{ account.account_name }}
                                                </span>
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>

                        <p
                            v-if="form.errors.parent_id"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.parent_id }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="is_posting_allowed"
                            v-model="form.is_posting_allowed"
                            type="checkbox"
                            class="size-4"
                        />
                        <label
                            for="is_posting_allowed"
                            class="text-sm font-medium"
                        >
                            Allow direct journal postings to this account
                        </label>
                    </div>

                    <div class="grid gap-2">
                        <label for="description" class="text-sm font-medium">
                            Description
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="bg-background rounded-md border px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors.description"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>

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
                            :disabled="form.processing"
                            class="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Account' }}
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Account Dialog -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Edit Account</DialogTitle>
                    <DialogDescription>
                        Update the selected account's information.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEditAccount" class="space-y-4">
                    <!-- Account Code -->
                    <div class="grid gap-2">
                        <label
                            for="edit_account_code"
                            class="text-sm font-medium"
                        >
                            Account Code
                        </label>

                        <input
                            id="edit_account_code"
                            v-model="editForm.account_code"
                            type="text"
                            maxlength="50"
                            placeholder="Enter account code"
                            class="bg-background rounded-md border px-3 py-2 text-sm"
                        />

                        <p class="text-muted-foreground text-xs">
                            You may enter a custom account code. Leaving this
                            field blank will preserve the existing account code.
                        </p>

                        <p
                            v-if="editForm.errors.account_code"
                            class="text-destructive text-xs"
                        >
                            {{ editForm.errors.account_code }}
                        </p>
                    </div>

                    <!-- Account Name -->
                    <div class="grid gap-2">
                        <label
                            for="edit_account_name"
                            class="text-sm font-medium"
                        >
                            Account Name *
                        </label>
                        <input
                            id="edit_account_name"
                            v-model="editForm.account_name"
                            type="text"
                            required
                            maxlength="150"
                            class="bg-background rounded-md border px-3 py-2 text-sm"
                        />
                        <p
                            v-if="editForm.errors.account_name"
                            class="text-destructive text-xs"
                        >
                            {{ editForm.errors.account_name }}
                        </p>
                    </div>

                    <!-- Account Type and Normal Balance -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <label
                                for="edit_account_type"
                                class="text-sm font-medium"
                            >
                                Account Type *
                            </label>
                            <select
                                id="edit_account_type"
                                v-model="editForm.account_type"
                                required
                                class="bg-background rounded-md border px-3 py-2 text-sm"
                            >
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                            <p
                                v-if="editForm.errors.account_type"
                                class="text-destructive text-xs"
                            >
                                {{ editForm.errors.account_type }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="edit_normal_balance"
                                class="text-sm font-medium"
                            >
                                Normal Balance *
                            </label>
                            <select
                                id="edit_normal_balance"
                                v-model="editForm.normal_balance"
                                required
                                class="bg-background rounded-md border px-3 py-2 text-sm"
                            >
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                            <p
                                v-if="editForm.errors.normal_balance"
                                class="text-destructive text-xs"
                            >
                                {{ editForm.errors.normal_balance }}
                            </p>
                        </div>
                    </div>

                    <!-- Parent Account -->
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Parent Account</label
                        >

                        <Popover>
                            <PopoverTrigger as-child>
                                <button
                                    type="button"
                                    role="combobox"
                                    class="bg-background flex w-full items-center justify-between gap-2 rounded-md border px-3 py-2 text-left text-sm"
                                >
                                    <span class="min-w-0 flex-1 truncate">
                                        {{
                                            parentAccounts.find(
                                                (account) =>
                                                    account.id ===
                                                    editForm.parent_id,
                                            )?.account_name ??
                                            'No Parent Account'
                                        }}
                                    </span>
                                    <ChevronsUpDown
                                        class="size-4 shrink-0 opacity-50"
                                    />
                                </button>
                            </PopoverTrigger>

                            <PopoverContent
                                class="w-[var(--reka-popover-trigger-width)] p-0"
                                align="start"
                            >
                                <Command>
                                    <CommandInput
                                        placeholder="Search parent account..."
                                    />

                                    <CommandList class="max-h-60">
                                        <CommandEmpty
                                            >No account found.</CommandEmpty
                                        >

                                        <CommandGroup>
                                            <CommandItem
                                                value="no-parent-account"
                                                @select="
                                                    () =>
                                                        (editForm.parent_id =
                                                            null)
                                                "
                                            >
                                                <Check
                                                    class="mr-2 size-4"
                                                    :class="
                                                        editForm.parent_id ===
                                                        null
                                                            ? 'opacity-100'
                                                            : 'opacity-0'
                                                    "
                                                />
                                                No Parent Account
                                            </CommandItem>

                                            <CommandItem
                                                v-for="account in editingAccount
                                                    ? getEligibleParentAccounts(
                                                          editingAccount,
                                                      )
                                                    : []"
                                                :key="account.id"
                                                :value="account.account_name"
                                                @select="
                                                    () =>
                                                        (editForm.parent_id =
                                                            account.id)
                                                "
                                            >
                                                <Check
                                                    class="mr-2 size-4"
                                                    :class="
                                                        editForm.parent_id ===
                                                        account.id
                                                            ? 'opacity-100'
                                                            : 'opacity-0'
                                                    "
                                                />
                                                <span class="truncate">
                                                    {{ account.account_name }}
                                                </span>
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>

                        <p
                            v-if="editForm.errors.parent_id"
                            class="text-destructive text-xs"
                        >
                            {{ editForm.errors.parent_id }}
                        </p>
                    </div>

                    <!-- Posting Permission -->
                    <div class="flex items-center gap-2">
                        <input
                            id="edit_is_posting_allowed"
                            v-model="editForm.is_posting_allowed"
                            type="checkbox"
                            class="size-4"
                        />
                        <label
                            for="edit_is_posting_allowed"
                            class="text-sm font-medium"
                        >
                            Allow direct journal postings to this account
                        </label>
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <label
                            for="edit_description"
                            class="text-sm font-medium"
                        >
                            Description
                        </label>
                        <textarea
                            id="edit_description"
                            v-model="editForm.description"
                            rows="3"
                            class="bg-background rounded-md border px-3 py-2 text-sm"
                        />
                        <p
                            v-if="editForm.errors.description"
                            class="text-destructive text-xs"
                        >
                            {{ editForm.errors.description }}
                        </p>
                    </div>

                    <DialogFooter>
                        <button
                            type="button"
                            @click="showEditDialog = false"
                            class="rounded-md border px-4 py-2 text-sm"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                        >
                            {{
                                editForm.processing
                                    ? 'Updating...'
                                    : 'Update Account'
                            }}
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Account Status Confirmation Dialog -->
        <Dialog v-model:open="showStatusDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            statusForm.is_active
                                ? 'Activate Account'
                                : 'Deactivate Account'
                        }}
                    </DialogTitle>

                    <DialogDescription>
                        <template v-if="selectedStatusAccount">
                            Are you sure you want to
                            {{
                                statusForm.is_active ? 'activate' : 'deactivate'
                            }}
                            <strong>{{
                                selectedStatusAccount.account_name
                            }}</strong
                            >?
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <div class="bg-muted/30 rounded-md border p-3 text-sm">
                    <template v-if="statusForm.is_active">
                        This account will become available for selection again.
                    </template>
                    <template v-else>
                        This account will no longer appear in active account
                        selections. Historical account information will be
                        preserved.
                    </template>
                </div>

                <p v-if="statusError" class="text-sm text-red-500">
                    {{ statusError }}
                </p>

                <DialogFooter>
                    <button
                        type="button"
                        @click="showStatusDialog = false"
                        class="rounded-md border px-4 py-2 text-sm"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="submitStatusUpdate"
                        class="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm font-medium"
                    >
                        Confirm
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
