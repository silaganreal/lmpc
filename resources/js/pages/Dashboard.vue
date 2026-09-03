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
            <h1 class="text-primary text-2xl font-semibold tracking-tight">
                Dashboard
            </h1>

            <p class="text-muted-foreground mt-1 text-sm">
                Welcome to the LMPC Cooperative Management System.
            </p>
        </div>

        <!-- Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Members -->
            <Link
                href="/members"
                class="group bg-card hover:border-primary/40 rounded-xl border p-5 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div
                        class="bg-secondary text-primary flex size-10 items-center justify-center rounded-lg"
                    >
                        <Users class="size-5" />
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-muted-foreground text-sm">Members</p>

                    <p class="mt-1 text-2xl font-semibold">—</p>

                    <p class="text-muted-foreground mt-1 text-xs">
                        View member records
                    </p>
                </div>
            </Link>

            <!-- Applications -->
            <div class="bg-card rounded-xl border p-5 shadow-sm">
                <div
                    class="bg-secondary text-primary flex size-10 items-center justify-center rounded-lg"
                >
                    <FileText class="size-5" />
                </div>

                <div class="mt-4">
                    <p class="text-muted-foreground text-sm">Applications</p>

                    <p class="mt-1 text-2xl font-semibold">—</p>

                    <p class="text-muted-foreground mt-1 text-xs">
                        Membership applications
                    </p>
                </div>
            </div>

            <!-- Share Capital -->
            <div class="bg-card rounded-xl border p-5 shadow-sm">
                <div
                    class="bg-secondary text-primary flex size-10 items-center justify-center rounded-lg"
                >
                    <Wallet class="size-5" />
                </div>

                <div class="mt-4">
                    <p class="text-muted-foreground text-sm">Share Capital</p>

                    <p class="mt-1 text-2xl font-semibold">—</p>

                    <p class="text-muted-foreground mt-1 text-xs">
                        Total share capital
                    </p>
                </div>
            </div>

            <!-- Loans -->
            <div class="bg-card rounded-xl border p-5 shadow-sm">
                <div
                    class="bg-secondary text-primary flex size-10 items-center justify-center rounded-lg"
                >
                    <CreditCard class="size-5" />
                </div>

                <div class="mt-4">
                    <p class="text-muted-foreground text-sm">Loans</p>

                    <p class="mt-1 text-2xl font-semibold">—</p>

                    <p class="text-muted-foreground mt-1 text-xs">
                        Loan portfolio
                    </p>
                </div>
            </div>
        </div>

        <!-- Getting Started -->
        <div class="bg-card rounded-xl border shadow-sm">
            <div class="border-b px-5 py-4">
                <h2 class="font-semibold">System Overview</h2>

                <p class="text-muted-foreground mt-1 text-sm">
                    Key cooperative operations will appear here as the system
                    modules are completed.
                </p>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <Link
                    href="/members"
                    class="hover:border-primary/40 hover:bg-secondary/40 rounded-lg border p-4 transition"
                >
                    <div class="flex items-center gap-3">
                        <Users class="text-primary size-5" />

                        <div>
                            <p class="font-medium">Manage Members</p>

                            <p class="text-muted-foreground text-sm">
                                View and manage cooperative member records.
                            </p>
                        </div>
                    </div>
                </Link>

                <div class="rounded-lg border p-4 opacity-70">
                    <div class="flex items-center gap-3">
                        <FileText class="text-primary size-5" />

                        <div>
                            <p class="font-medium">Membership Applications</p>

                            <p class="text-muted-foreground text-sm">
                                Application processing will be available here.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
