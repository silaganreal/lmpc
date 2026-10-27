<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface User {
    id: number;
    name: string;
}

interface Loan {
    id: number;
    loan_no: string;
    outstanding_principal: string | number;
    member?: {
        first_name?: string;
        last_name?: string;
        name?: string;
    } | null;
}

interface Restructuring {
    id: number;
    loan_id: number;
    loan: Loan;
    requested_by: User;
    reviewed_by?: User | null;
    request_date: string;
    request_reason: string;
    requested_term_years: number;
    approved_term_years: number | null;
    status: string;
    board_remarks: string | null;
    remaining_principal: string | number | null;
    deferred_interest: string | number;
    penalties: string | number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    restructurings: {
        data: Restructuring[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
}>();

const page = usePage();

const isReviewer = ['admin', 'board'].includes(
    String((page.props.auth as { user?: { role?: string } })?.user?.role ?? ''),
);

const forms = reactive<
    Record<
        number,
        {
            approved_term_years: number;
            board_remarks: string;
        }
    >
>({});

for (const item of props.restructurings.data) {
    forms[item.id] = {
        approved_term_years: item.requested_term_years,
        board_remarks: '',
    };
}

function approve(item: Restructuring) {
    const form = forms[item.id];

    if (!form) return;

    if (!window.confirm(`Approve restructuring for ${item.loan.loan_no}?`)) {
        return;
    }

    router.patch(`/loan-restructurings/${item.id}/approve`, {
        approved_term_years: form.approved_term_years,
        board_remarks: form.board_remarks || null,
    });
}

function reject(item: Restructuring) {
    const form = forms[item.id];

    if (!form) return;

    if (!form.board_remarks.trim()) {
        window.alert('Please enter the reason for rejection in Board Remarks.');
        return;
    }

    if (!window.confirm(`Reject restructuring for ${item.loan.loan_no}?`)) {
        return;
    }

    router.patch(`/loan-restructurings/${item.id}/reject`, {
        board_remarks: form.board_remarks,
    });
}

function peso(value: string | number | null | undefined): string {
    return Number(value ?? 0).toLocaleString('en-PH', {
        style: 'currency',
        currency: 'PHP',
    });
}

function memberName(loan: Loan): string {
    const member = loan.member;

    if (!member) return 'Member unavailable';

    if (member.name) return member.name;

    return (
        [member.first_name, member.last_name].filter(Boolean).join(' ') ||
        'Member'
    );
}
</script>

<template>
    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold">Loan Restructuring Requests</h1>
            <p class="mt-1 text-sm text-gray-500">
                Review requests and record the board's decision.
            </p>
        </div>

        <div class="rounded-lg border p-4">
            <p class="text-sm text-gray-500">Total requests</p>
            <p class="text-2xl font-semibold">{{ restructurings.total }}</p>
        </div>

        <div
            v-if="restructurings.data.length === 0"
            class="rounded-lg border p-8 text-center text-gray-500"
        >
            No restructuring requests found.
        </div>

        <div
            v-for="item in restructurings.data"
            :key="item.id"
            class="space-y-4 rounded-lg border p-5"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">
                        {{ item.loan.loan_no }}
                    </h2>
                    <p class="text-sm text-gray-600">
                        {{ memberName(item.loan) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        Requested by
                        {{ item.requested_by?.name ?? 'Unknown user' }} ·
                        {{ item.request_date }}
                    </p>
                </div>

                <span
                    class="rounded-full bg-gray-100 px-3 py-1 text-sm capitalize"
                >
                    {{ item.status }}
                </span>
            </div>

            <div>
                <p class="text-sm font-medium">Reason for request</p>
                <p class="text-sm whitespace-pre-wrap text-gray-600">
                    {{ item.request_reason }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-sm text-gray-500">Requested term</p>
                    <p class="font-medium">
                        {{ item.requested_term_years }} year(s)
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Recorded principal</p>
                    <p class="font-medium">
                        {{
                            peso(
                                item.remaining_principal ??
                                    item.loan.outstanding_principal,
                            )
                        }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">
                        Deferred interest / penalties
                    </p>
                    <p class="font-medium">
                        {{ peso(item.deferred_interest) }} /
                        {{ peso(item.penalties) }}
                    </p>
                </div>
            </div>

            <div
                v-if="item.status === 'pending' && isReviewer"
                class="space-y-4 border-t pt-4"
            >
                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Approved term
                    </label>
                    <select
                        v-model.number="forms[item.id].approved_term_years"
                        class="w-full rounded-md border px-3 py-2 sm:max-w-xs"
                    >
                        <option
                            v-for="year in [1, 2, 3, 4, 5]"
                            :key="year"
                            :value="year"
                        >
                            {{ year }} year(s)
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Board remarks
                    </label>
                    <textarea
                        v-model="forms[item.id].board_remarks"
                        rows="2"
                        maxlength="5000"
                        class="w-full rounded-md border px-3 py-2"
                        placeholder="Enter decision remarks; required for rejection."
                    />
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="rounded-md bg-green-700 px-4 py-2 text-white hover:bg-green-800"
                        @click="approve(item)"
                    >
                        Approve
                    </button>

                    <button
                        type="button"
                        class="rounded-md bg-red-600 px-4 py-2 text-white hover:bg-red-700"
                        @click="reject(item)"
                    >
                        Reject
                    </button>
                </div>
            </div>

            <div
                v-else-if="item.status !== 'pending'"
                class="border-t pt-4 text-sm"
            >
                <p v-if="item.approved_term_years">
                    Approved term: {{ item.approved_term_years }} year(s)
                </p>
                <p v-if="item.reviewed_by">
                    Reviewed by: {{ item.reviewed_by.name ?? 'Unknown user' }}
                </p>
                <p v-if="item.board_remarks">
                    Board remarks: {{ item.board_remarks }}
                </p>
            </div>
        </div>

        <div v-if="restructurings.last_page > 1" class="flex flex-wrap gap-2">
            <button
                v-for="link in restructurings.links"
                :key="link.label"
                type="button"
                :disabled="!link.url"
                class="rounded border px-3 py-2 text-sm disabled:opacity-40"
                :class="{ 'bg-gray-200 font-semibold': link.active }"
                @click="link.url && router.visit(link.url)"
                v-html="link.label"
            />
        </div>
    </div>
</template>
