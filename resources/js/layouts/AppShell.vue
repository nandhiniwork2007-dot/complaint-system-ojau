<template>
    <div class="min-h-screen bg-bg text-text-main selection:bg-primary/30 selection:text-text-main">
        <!-- Sidebar -->
        <aside
            class="fixed left-0 top-0 bottom-0 w-72 z-40 border-r border-surface-border/60 bg-sidebar backdrop-blur-xl transition-transform duration-300 ease-in-out"
            :class="{ '-translate-x-full': isMobile && !isSidebarOpen }"
        >
            <div class="flex flex-col h-full">
                <!-- Brand -->
                <div class="flex items-center gap-3 h-14 px-5 shrink-0 border-b border-surface-border/40">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-linear-to-br from-primary via-violet-500 to-primary shadow-lg shadow-primary/25">
                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-text-main leading-tight tracking-tight">Career <span class="text-primary">Next</span></div>
                        <div class="text-[10px] text-text-muted uppercase tracking-widest font-semibold">Support Portal</div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto py-4 px-3">
                    <template v-for="group in navGroups">
                        <div
                            v-if="group.items.length"
                            :key="group.label"
                            class="mb-4"
                        >
                            <div class="mb-1.5 text-[10px] font-bold text-text-muted uppercase tracking-widest px-2">
                                {{ group.label }}
                            </div>
                            <RouterLink
                                v-for="item in group.items"
                                :key="item.to"
                                :to="item.to"
                                class="flex items-center gap-3 px-2.5 py-2 rounded-lg mb-0.5 text-sm transition-all duration-150 group/nav"
                                :class="isActive(item.to) ? 'bg-primary/10 text-primary border border-primary/20' : 'text-text-secondary hover:text-text-main hover:bg-surface/50'"
                            >
                                <Icon :name="item.icon" :class="isActive(item.to) ? 'text-primary' : 'text-text-muted group-hover/nav:text-text-main'" class="h-4 w-4 shrink-0 transition-colors" />
                                <span class="font-medium">{{ item.label }}</span>
                                <span v-if="item.badge" class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-primary/20 text-primary">{{ item.badge }}</span>
                            </RouterLink>
                        </div>
                    </template>
                </nav>

                <!-- User section -->
                <div class="px-3 py-4 border-t border-surface-border/40 shrink-0">
                    <div class="relative">
                        <div
                            class="flex items-center gap-3 px-2 py-2 rounded-lg cursor-pointer hover:bg-surface/50 transition-colors"
                            @click="toggleProfileMenu"
                        >
                            <Avatar :src="user.avatar" :name="user.name" size="md" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-text-main truncate">{{ user.name }}</p>
                                <p class="text-[11px] text-text-muted uppercase tracking-wider font-semibold">{{ user.roleLabel }}</p>
                            </div>
                            <ChevronDownIcon :class="['h-4 w-4 text-text-muted transition-transform duration-200', isProfileMenuOpen ? 'rotate-180' : '']" />
                        </div>

                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-1"
                        >
                            <div v-if="isProfileMenuOpen" class="absolute bottom-full left-0 right-0 mb-2 z-50">
                                <div class="rounded-xl border border-surface-border bg-surface shadow-xl p-1.5">
                                    <button
                                        v-for="item in profileMenuItems"
                                        :key="item.label"
                                        @click="() => { isProfileMenuOpen = false; if (item.action) item.action(); else router.push(item.to); }"
                                        class="flex w-full items-center gap-3 px-3 py-2.5 text-sm text-text-main rounded-lg hover:bg-surface-elevated transition-colors"
                                    >
                                        <Icon :name="item.icon" class="h-4 w-4 text-text-muted" />
                                        <span>{{ item.label }}</span>
                                    </button>
                                </div>
                            </div>
                        </Transition>

                        <button
                            @click="handleLogout"
                            class="mt-2 w-full flex items-center gap-3 px-2 py-2 text-sm text-error rounded-lg hover:bg-error/10 transition-colors"
                        >
                            <LogOutIcon class="h-4 w-4" />
                            <span class="font-medium">Sign out</span>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile overlay -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-out"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isMobile && isSidebarOpen"
                class="fixed inset-0 z-30 bg-black/70 backdrop-blur-sm"
                @click="toggleSidebar"
            ></div>
        </Transition>

        <!-- App body -->
        <div
            class="flex min-h-screen flex-col transition-all duration-300"
            :class="isMobile ? 'pl-0' : 'pl-72'"
        >
            <!-- Top navigation -->
            <header class="sticky top-0 z-30 shrink-0 border-b border-surface-border/40 bg-bg/80 backdrop-blur-xl">
                <div class="flex min-h-14 items-center gap-3 py-3" :class="isMobile ? 'px-4' : 'px-6 lg:px-8'">
                    <button
                        @click="toggleSidebar"
                        class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg text-text-muted hover:text-text-main hover:bg-surface-elevated transition-colors"
                        :class="{ 'hidden': !isMobile }"
                        aria-label="Toggle navigation"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <div class="flex-1">
                        <PageHeader
                            :title="pageTitle"
                            :subtitle="pageSubtitle"
                            :breadcrumbs="breadcrumbs"
                        >
                            <template #actions>
                                <div class="flex flex-wrap items-center gap-2">
                                    <slot name="actions" />
                                    <ThemeControl />
                                    <button
                                        class="hidden h-8 w-8 items-center justify-center rounded-lg text-text-muted transition-colors hover:bg-surface-elevated hover:text-text-main xl:flex"
                                        aria-label="Search"
                                    >
                                        <SearchIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        class="relative hidden h-8 w-8 items-center justify-center rounded-lg text-text-muted transition-colors hover:bg-surface-elevated hover:text-text-main xl:flex"
                                        aria-label="Notifications"
                                    >
                                        <BellIcon class="h-4 w-4" />
                                        <span v-if="notificationCount" class="absolute -top-0.5 -right-0.5 h-4 w-4 flex items-center justify-center rounded-full bg-error text-[10px] font-bold text-white"></span>
                                    </button>
                                    <Avatar
                                        :name="user.name"
                                        :src="user.avatar"
                                        size="sm"
                                        class="bg-surface-elevated ring-2 ring-surface-border"
                                    />
                                </div>
                            </template>
                        </PageHeader>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="relative flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto w-full max-w-7xl">
                    <slot />
                </div>
            </main>
            <AppFooter />
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import {
    ChevronDownIcon,
    LogOutIcon,
    SearchIcon,
    BellIcon,
} from '@lucide/vue';

import Avatar from '../components/Avatar.vue';
import Icon from '../components/Icon.vue';
import PageHeader from '../components/PageHeader.vue';
import ThemeControl from '../components/ThemeControl.vue';
import AppFooter from '../components/AppFooter.vue';

const props = defineProps({
    section: { type: String, default: 'user' },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
});

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const isMobile = ref(window.innerWidth < 768);
const isSidebarOpen = ref(!isMobile.value);
const isProfileMenuOpen = ref(false);

const notificationCount = ref(0);

const user = computed(() => {
    const u = auth.user;
    if (!u) {
        return { name: 'Guest', roleLabel: '', avatar: '' };
    }
    return {
        name: u.name,
        roleLabel: u.role === 'staff' ? 'Administrator' : u.role.charAt(0).toUpperCase() + u.role.slice(1).replace('_', ' '),
        avatar: u.avatar ?? '',
    };
});

const isStaff = computed(() => auth.isStaff);

function isActive(path) {
    if (route.path === path) {
        return true;
    }

    if (path === '/complaints/new' || path === '/dashboard') {
        return false;
    }

    return route.path.startsWith(`${path}/`);
}

const navGroups = computed(() => {
    if (isStaff.value) {
        return [
            {
                label: 'Administration',
                items: [
                    { to: '/admin/dashboard', label: 'Dashboard', icon: 'LayoutDashboardIcon' },
                    { to: '/admin/complaints', label: 'Complaints', icon: 'FileTextIcon' },
                    { to: '/admin/categories', label: 'Categories', icon: 'TagIcon' },
                    { to: '/admin/users', label: 'Users', icon: 'UsersIcon' },
                    { to: '/admin/reports', label: 'Reports & Analytics', icon: 'PieChartIcon' },
                    ...(auth.user?.can_manage_users ? [{ to: '/admin/audit', label: 'Audit History', icon: 'ShieldAlertIcon' }] : []),
                    { to: '/admin/settings', label: 'Settings', icon: 'SettingsIcon' },
                ],
            },
        ];
    }

    return [
        {
            label: 'Workspace',
            items: [
                { to: '/dashboard', label: 'Dashboard', icon: 'LayoutDashboardIcon' },
                { to: '/complaints', label: 'My Complaints', icon: 'FileTextIcon' },
                { to: '/complaints/new', label: 'Submit Complaint', icon: 'FilePlusIcon' },
                { to: '/profile', label: 'Profile', icon: 'UserIcon' },
            ],
        },
    ];
});

const profileMenuItems = computed(() => {
    const items = [
        { label: 'Profile', to: '/profile', icon: 'UserIcon' },
    ];
    if (isStaff.value) {
        items.push({ label: 'Administration', to: '/admin/dashboard', icon: 'ShieldCheckIcon' });
    }
    return items;
});

const routeTitles = {
    '/dashboard': 'Dashboard',
    '/complaints': 'My Complaints',
    '/complaints/new': 'Submit Complaint',
    '/complaints/:id': 'Complaint Details',
    '/profile': 'Profile & Settings',
    '/admin/dashboard': 'Admin Dashboard',
    '/admin/complaints': 'Complaint Management',
    '/admin/complaints/:id': 'Complaint Review',
    '/admin/categories': 'Categories',
    '/admin/users': 'User Management',
    '/admin/reports': 'Reports & Analytics',
    '/admin/audit': 'Audit History',
    '/admin/settings': 'System Settings',
};

const routeSubtitles = {
    '/dashboard': 'Your personalized overview.',
    '/complaints': 'Track and manage your submitted complaints.',
    '/complaints/new': 'Describe the issue so the team can act quickly.',
    '/profile': 'Manage your personal details and security.',
    '/admin/dashboard': 'Operational command center.',
    '/admin/complaints': 'Review and manage all complaints.',
    '/admin/categories': 'Manage the complaint taxonomy.',
    '/admin/users': 'Manage accounts, roles, and status.',
    '/admin/reports': 'Review complaint trends and outcomes.',
    '/admin/audit': 'Review important actions taken in the system.',
    '/admin/settings': 'Control system-wide preferences.',
};

/** Literal paths must win over `:id` patterns so /complaints/new is not read as a detail page. */
function fromRouteMap(map, fallback) {
    const keys = Object.keys(map).sort((a, b) => {
        const aIsPattern = a.includes(':') ? 1 : 0;
        const bIsPattern = b.includes(':') ? 1 : 0;

        if (aIsPattern !== bIsPattern) {
            return aIsPattern - bIsPattern;
        }

        return b.length - a.length;
    });

    for (const key of keys) {
        if (new RegExp(`^${key.replace(':id', '[^/]+')}$`).test(route.path)) {
            return map[key];
        }
    }

    return fallback;
}

const pageTitle = computed(() => props.title || fromRouteMap(routeTitles, 'Dashboard'));

const pageSubtitle = computed(() => props.subtitle || fromRouteMap(routeSubtitles, ''));

const breadcrumbs = computed(() => {
    const path = route.path;
    const crumbs = [{ label: 'Home', to: '/' }];
    if (path.startsWith('/admin')) {
        crumbs.push({ label: 'Administration' });
    } else if (path === '/dashboard') {
        crumbs.push({ label: 'Dashboard' });
    } else if (path.startsWith('/complaints')) {
        crumbs.push({ label: 'Complaints', to: '/complaints' });
    } else if (path === '/profile') {
        crumbs.push({ label: 'Profile' });
    }
    return crumbs;
});

function toggleSidebar() {
    if (isMobile.value) {
        isSidebarOpen.value = !isSidebarOpen.value;
    }
}

function toggleProfileMenu() {
    isProfileMenuOpen.value = !isProfileMenuOpen.value;
}

async function handleLogout() {
    try {
        await auth.logout();
        router.push('/login');
    } catch {
        router.push('/login');
    }
}

onMounted(() => {
    const handleResize = () => {
        const mobile = window.innerWidth < 768;
        isMobile.value = mobile;
        if (!mobile) {
            isSidebarOpen.value = true;
            isProfileMenuOpen.value = false;
        }
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
});

watch(
    () => auth.user,
    () => {
        if (auth.isAuthenticated && isMobile.value) {
            isSidebarOpen.value = false;
        }
    },
);
</script>
