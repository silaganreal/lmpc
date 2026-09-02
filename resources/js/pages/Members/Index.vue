<script setup lang="ts">
    import { Head, Link } from '@inertiajs/vue3';

    interface Member {
        id: number
        member_no: string
        first_name: string
        middle_name: string
        last_name: string
        suffix: string | null
        membership_type: string | null
        status: string
    }

    interface PaginatedMembers {
        data: Member[]
        current_page: number
        last_page: number
        total: number
    }

    defineProps<{
        members: PaginatedMembers
    }>()

    const getStatusClass = (status: string) => {
        switch (status.toLowerCase()) {
            case 'active':
                return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
            case 'pending':
                return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
            case 'suspended':
                return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
            case 'terminated':
            case 'inactive':
                return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
            default:
                return 'bg-muted text-muted-foreground';
        }
    }
</script>

<template>
    <Head title="Members"/>
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-primary">Members</h1>

                <p class="mt-1 text-sm text-muted-foreground">Manage cooperative members and membership information.</p>
            </div>

            <Link
                href="/members/create"
                class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition hover:opacity-90"
            >
                Add Member
            </Link>
        </div>

        <!-- Members Card -->
        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b bg-muted/30 px-5 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold">Cooperative Members</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ members.total }} total member{{ members.total === 1 ? '' : 's' }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Member No.</th>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Membership Type</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="member in members.data"
                            :key="member.id"
                            class="border-b last:border-b-0 transition hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium text-primary">{{ member.member_no }}</td>
                            <td class="px-4 py-3 font-medium">
                                <div class="font-medium">
                                    {{ member.last_name }},
                                    {{ member.first_name }}
                                    {{ member.middle_name ?? '' }}
                                    {{ member.suffix ?? '' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 capitalize">
                                {{ member.membership_type ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                                    :class="getStatusClass(member.status)"
                                >
                                    {{ member.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="`/members/${member.id}`"
                                    class="font-medium text-primary hover:underline"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>

                        <tr v-if="members.data.length === 0">
                            <td
                                colspan="5"
                                class="px-5 py-12 text-center"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <div class="flex size-12 items-center justify-center rounded-full bg-secondary text-primary">
                                        👥
                                    </div>
                                    <p class="font-medium">
                                        No members found
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        Start by adding your first cooperative member.
                                    </p>
                                    <Link
                                        href="/members/create"
                                        class="mt-2 font-medium text-primary hover:underline"
                                    >
                                        Add your first member
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Footer -->
             <div class="border-t bg-muted/20 px-5 py-3 text-sm text-muted-foreground">
                Showing {{ members.data.length }} of {{ members.total }} members.
             </div>
        </div>
    </div>
</template>