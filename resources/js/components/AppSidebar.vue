<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { LayoutGrid, LucideGroup } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import { useLang } from '@/composables/useLang';
import { dashboard } from '@/routes';
import SidebarGroup from './ui/sidebar/SidebarGroup.vue';
import SidebarGroupContent from './ui/sidebar/SidebarGroupContent.vue';
import SidebarGroupLabel from './ui/sidebar/SidebarGroupLabel.vue';

const { t, locale } = useLang();

type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

type NavGroup = { label: string; items: NavItem[] };

const mainNavGroups = computed<NavGroup[]>(() => [
    {
        label: t('dashboard.title'),
        items: [
            {
                title: t('sidebar.dashboard', 'Dashboard'),
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
    },
    {
        label: t('blog.title'),
        items: [
            {
                title: t('categories.title', 'Categories'),
                href: '/dashboard/categories',
                icon: LucideGroup,
            },
        ],
    },
]);

const footerNavItems = computed<NavItem[]>(() => [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: FolderGit2,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
]);

const sidebarSide = computed<'left' | 'right'>(() =>
    locale.value === 'ar' ? 'right' : 'left',
);
</script>

<template>
    <Sidebar :side="sidebarSide" collapsible="icon" variant="inset">
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
            <SidebarGroup v-for="group in mainNavGroups" :key="group.label">
                <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <NavMain :items="group.items" />
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
