<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref } from 'vue';
import { Search, BookOpen } from 'lucide-vue-next';

interface Account {
    id: number;
    account_code: string | null;
    account_name: string;
    account_type: 'asset' | 'liability' | 'equity' | 'income' | 'expense';
    normal_balance: 'debit' | 'credit';
    parent: {
        id: number;
        account_code: string | null;
        account_name: string;
    } | null;
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
    start_date: string;
    end_date: string;
}

const props = defineProps<{
    accounts: PaginatedAccounts;
    filters: Filters;
}>();

const search = ref(props.filters.search);

const applyFilters = () => {
    router.get(
        route('accounting.general-ledger.index'),
        {
            search: search.value || undefined,
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

    router.get(
        route('accounting.general-ledger.index'),
        {},
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
    <Head title="General Ledger" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                General Ledger
            </h1>

            <p class="text-muted-foreground mt-1 text-sm">
                View account transactions, debit and credit movements, and
                running balances.
            </p>
        </div>

        <!-- Summary -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl border p-5">
                <p class="text-muted-foreground text-sm">Posting Accounts</p>
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
                <p class="text-muted-foreground text-sm">Displayed Accounts</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ accounts.data.length }}
                </p>
            </div>
        </div>

        <!-- Account Register -->
        <div class="overflow-hidden rounded-xl border">
            <div class="border-b p-4">
                <h2 class="font-semibold">Select an Account</h2>

                <p class="text-muted-foreground mt-1 text-sm">
                    Select an account to view its General Ledger transactions.
                </p>
            </div>

            <!-- Search -->
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

                <button
                    type="submit"
                    class="bg-primary text-primary-foreground inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium"
                >
                    <Search class="size-4" />
                    Search
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

                            <th class="px-4 py-3 text-right font-medium">
                                Action
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

                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="
                                        route(
                                            'accounting.general-ledger.show',
                                            account.id,
                                        )
                                    "
                                    class="bg-primary text-primary-foreground inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium"
                                >
                                    <BookOpen class="size-4" />
                                    View Ledger
                                </Link>
                            </td>
                        </tr>

                        <tr v-if="accounts.data.length === 0">
                            <td
                                colspan="6"
                                class="text-muted-foreground px-4 py-10 text-center"
                            >
                                No posting accounts found.
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
    </div>
</template>
