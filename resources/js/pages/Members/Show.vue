<script setup lang="ts">
    import { Link } from '@inertiajs/vue3';

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
</script>

<template>
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Members</h1>

                <p class="text-sm text-muted-foreground">Manage cooperative members.</p>
            </div>

            <Link
                href="/members/create"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
            >
                Add Member
            </Link>
        </div>

        <div class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left">Member No.</th>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Membership Type</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="member in members.data"
                        :key="member.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3">{{ member.member_no }}</td>
                        <td class="px-4 py-3 font-medium">
                            {{ member.last_name }},
                            {{ member.first_name }}
                            {{ member.middle_name ?? '' }}
                            {{ member.suffix ?? '' }}
                        </td>
                        <td class="px-4 py-3 capitalize">
                            {{ member.membership_type ?? '-' }}
                        </td>
                        <td class="px-4 py-3 capitalize">
                            {{ member.status }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="`/members${member.id}`"
                                class="text-primary hover:underline"
                            >
                                View
                            </Link>
                        </td>
                    </tr>

                    <tr v-if="members.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No members found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-sm text-muted-foreground">
            Showing {{ members.data.length }} of {{ members.total }} members.
        </div>
    </div>
</template>