<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed } from 'vue';
import { ArrowLeft, Plus, Trash2 } from 'lucide-vue-next';

interface ChartOfAccount {
    id: number;
    account_code: string | null;
    account_name: string;
    account_type: string;
    normal_balance: 'debit' | 'credit';
}

interface ExistingLine {
    id: number;
    line_number: number;
    chart_of_account_id: number;
    description: string | null;
    debit: string | number;
    credit: string | number;
}

interface JournalEntry {
    id: number;
    entry_number: string;
    entry_date: string;
    reference_number: string | null;
    description: string;
    status: 'draft' | 'posted' | 'reversed';
    lines: ExistingLine[];
}

const props = defineProps<{
    entry: JournalEntry;
    chartOfAccounts: ChartOfAccount[];
}>();

interface FormLine {
    chart_of_account_id: number | null;
    description: string;
    debit: string;
    credit: string;
}

const newLine = (): FormLine => ({
    chart_of_account_id: null,
    description: '',
    debit: '',
    credit: '',
});

const form = useForm({
    entry_date: props.entry.entry_date.substring(0, 10),
    reference_number: props.entry.reference_number ?? '',
    description: props.entry.description,
    lines: props.entry.lines.map((line) => ({
        chart_of_account_id: line.chart_of_account_id,
        description: line.description ?? '',
        debit: Number(line.debit) > 0 ? String(line.debit) : '',
        credit: Number(line.credit) > 0 ? String(line.credit) : '',
    })) as FormLine[],
});

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

const formatAmount = (amount: number) =>
    new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(amount);

const accountLabel = (account: ChartOfAccount) =>
    `${account.account_code ? account.account_code + ' - ' : ''}${account.account_name}`;

const addLine = () => {
    form.lines.push(newLine());
};

const removeLine = (index: number) => {
    if (form.lines.length <= 2) return;

    form.lines.splice(index, 1);
};

const submitUpdate = () => {
    form.put(route('accounting.journal-entries.update', props.entry.id), {
        preserveScroll: true,
    });
};

const cancelEdit = () => {
    router.visit(route('accounting.journal-entries.show', props.entry.id));
};
</script>

<template>
    <Head :title="`Edit ${entry.entry_number}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div>
            <Link
                :href="route('accounting.journal-entries.show', entry.id)"
                class="text-muted-foreground hover:text-foreground mb-3 inline-flex items-center gap-2 text-sm"
            >
                <ArrowLeft class="size-4" />
                Back to Journal Entry
            </Link>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Edit Journal Entry
                    </h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Update the details and accounting lines of this draft.
                    </p>
                </div>

                <span
                    class="inline-flex rounded-full bg-amber-100 px-3 py-1.5 text-sm font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                >
                    Draft
                </span>
            </div>
        </div>

        <!-- Entry Number -->
        <div class="rounded-xl border p-5">
            <p class="text-muted-foreground text-sm">Journal Entry Number</p>
            <p class="mt-1 text-xl font-semibold">
                {{ entry.entry_number }}
            </p>
            <p class="text-muted-foreground mt-1 text-xs">
                The journal entry number cannot be changed.
            </p>
        </div>

        <form @submit.prevent="submitUpdate" class="space-y-6">
            <!-- Entry Information -->
            <div class="rounded-xl border">
                <div class="border-b p-4">
                    <h2 class="font-semibold">Transaction Information</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Update the date, reference, and description.
                    </p>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
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
                            class="bg-background rounded-md border px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors.reference_number"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.reference_number }}
                        </p>
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <label for="description" class="text-sm font-medium">
                            Transaction Description *
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            maxlength="5000"
                            required
                            class="bg-background rounded-md border px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors.description"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Journal Entry Lines -->
            <div class="overflow-hidden rounded-xl border">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b p-4"
                >
                    <div>
                        <h2 class="font-semibold">Journal Entry Lines</h2>
                        <p class="text-muted-foreground mt-1 text-sm">
                            Modify existing lines or add new ones.
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

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="w-12 px-3 py-3">#</th>
                                <th class="min-w-64 px-3 py-3">Account *</th>
                                <th class="min-w-48 px-3 py-3">Description</th>
                                <th class="w-36 px-3 py-3 text-right">Debit</th>
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

                                <td class="px-3 py-3">
                                    <select
                                        v-model="line.chart_of_account_id"
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
                                            form.errors[`lines.${index}.debit`]
                                        "
                                        class="text-destructive mt-1 text-xs"
                                    >
                                        {{
                                            form.errors[`lines.${index}.debit`]
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
                                            form.errors[`lines.${index}.credit`]
                                        "
                                        class="text-destructive mt-1 text-xs"
                                    >
                                        {{
                                            form.errors[`lines.${index}.credit`]
                                        }}
                                    </p>
                                </td>

                                <td class="px-3 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="removeLine(index)"
                                        :disabled="form.lines.length <= 2"
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
                                <td colspan="3" class="px-3 py-3 text-right">
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

                <div class="border-t p-4">
                    <p
                        v-if="form.errors.lines"
                        class="text-destructive text-xs"
                    >
                        {{ form.errors.lines }}
                    </p>

                    <div
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4"
                    >
                        <div>
                            <p class="text-sm font-semibold">
                                Debit / Credit Difference
                            </p>
                            <p class="text-muted-foreground text-xs">
                                Draft entries may remain unbalanced until
                                posting.
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
                                {{ isBalanced ? 'Balanced' : 'Not Balanced' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    @click="cancelEdit"
                    class="hover:bg-muted rounded-md border px-4 py-2 text-sm font-medium"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-primary text-primary-foreground rounded-md px-5 py-2 text-sm font-medium disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving Changes...' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</template>
