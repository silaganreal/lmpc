<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Users, FileText, Wallet, CreditCard } from '@lucide/vue';

import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Welcome -->
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-primary">
                Dashboard
            </h1>

            <p class="mt-1 text-sm text-muted-foreground">
                Welcome to the LMPC Cooperative Management System.
            </p>
        </div>

        <!-- Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Members -->
            <Link
                href="/members"
                class="group rounded-xl border bg-card p-5 shadow-sm transition hover:border-primary/40 hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-secondary text-primary"
                    >
                        <Users class="size-5" />
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-sm text-muted-foreground">
                        Members
                    </p>

                    <p class="mt-1 text-2xl font-semibold">
                        —
                    </p>

                    <p class="mt-1 text-xs text-muted-foreground">
                        View member records
                    </p>
                </div>
            </Link>

            <!-- Applications -->
            <div
                class="rounded-xl border bg-card p-5 shadow-sm"
            >
                <div
                    class="flex size-10 items-center justify-center rounded-lg bg-secondary text-primary"
                >
                    <FileText class="size-5" />
                </div>

                <div class="mt-4">
                    <p class="text-sm text-muted-foreground">
                        Applications
                    </p>

                    <p class="mt-1 text-2xl font-semibold">
                        —
                    </p>

                    <p class="mt-1 text-xs text-muted-foreground">
                        Membership applications
                    </p>
                </div>
            </div>

            <!-- Share Capital -->
            <div
                class="rounded-xl border bg-card p-5 shadow-sm"
            >
                <div
                    class="flex size-10 items-center justify-center rounded-lg bg-secondary text-primary"
                >
                    <Wallet class="size-5" />
                </div>

                <div class="mt-4">
                    <p class="text-sm text-muted-foreground">
                        Share Capital
                    </p>

                    <p class="mt-1 text-2xl font-semibold">
                        —
                    </p>

                    <p class="mt-1 text-xs text-muted-foreground">
                        Total share capital
                    </p>
                </div>
            </div>

            <!-- Loans -->
            <div
                class="rounded-xl border bg-card p-5 shadow-sm"
            >
                <div
                    class="flex size-10 items-center justify-center rounded-lg bg-secondary text-primary"
                >
                    <CreditCard class="size-5" />
                </div>

                <div class="mt-4">
                    <p class="text-sm text-muted-foreground">
                        Loans
                    </p>

                    <p class="mt-1 text-2xl font-semibold">
                        —
                    </p>

                    <p class="mt-1 text-xs text-muted-foreground">
                        Loan portfolio
                    </p>
                </div>
            </div>
        </div>

        <!-- Getting Started -->
        <div class="rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-4">
                <h2 class="font-semibold">
                    System Overview
                </h2>

                <p class="mt-1 text-sm text-muted-foreground">
                    Key cooperative operations will appear here as the system modules are completed.
                </p>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <Link
                    href="/members"
                    class="rounded-lg border p-4 transition hover:border-primary/40 hover:bg-secondary/40"
                >
                    <div class="flex items-center gap-3">
                        <Users class="size-5 text-primary" />

                        <div>
                            <p class="font-medium">
                                Manage Members
                            </p>

                            <p class="text-sm text-muted-foreground">
                                View and manage cooperative member records.
                            </p>
                        </div>
                    </div>
                </Link>

                <div
                    class="rounded-lg border p-4 opacity-70"
                >
                    <div class="flex items-center gap-3">
                        <FileText class="size-5 text-primary" />

                        <div>
                            <p class="font-medium">
                                Membership Applications
                            </p>

                            <p class="text-sm text-muted-foreground">
                                Application processing will be available here.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>