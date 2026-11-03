<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import {
    BookOpen,
    Calculator,
    FileBarChart,
    FileText,
    LayoutGrid,
    ListTree,
    Scale,
    Users,
    Wallet,
} from '@lucide/vue';

import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Membership Applications',
        href: '/membership-applications',
        icon: FileText,
    },
    {
        title: 'Members',
        href: '/members',
        icon: Users,
    },
    {
        title: 'Collections',
        href: '/collections',
        icon: Wallet,
    },
];

const accountingNavItems: NavItem[] = [
    {
        title: 'Chart of Accounts',
        href: '/accounting/chart-of-accounts',
        icon: ListTree,
    },
    {
        title: 'Journal Entries',
        href: '/accounting/journal-entries',
        icon: BookOpen,
    },
    {
        title: 'General Ledger',
        href: '/accounting/general-ledger',
        icon: Scale,
    },
    {
        title: 'Trial Balance',
        href: '/accounting/trial-balance',
        icon: Calculator,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="MAIN" />

            <NavMain :items="accountingNavItems" label="ACCOUNTING" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
