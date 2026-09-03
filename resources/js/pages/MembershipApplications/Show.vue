<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Membership Application" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">
                Membership Application
            </h1>

            <p class="text-muted-foreground">
                View membership application details.
            </p>
        </div>

        <div class="bg-card rounded-xl border p-6 shadow-sm">
            <p class="text-muted-foreground text-sm">
                Application details coming next.
            </p>
        </div>
    </div>
</template>
