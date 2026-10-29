<template>
    <component :is="icon" class="shrink-0" />
</template>

<script setup>
import { computed } from 'vue';
import {
    LayoutDashboardIcon,
    FileTextIcon,
    FilePlusIcon,
    UserIcon,
    ShieldCheckIcon,
    SettingsIcon,
    UsersIcon,
    TagIcon,
    BellIcon,
    SearchIcon,
    LogOutIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    MenuIcon,
    ArrowRightIcon,
    TrendingUpIcon,
    PieChartIcon,
    ClockIcon,
    MailIcon,
    PhoneIcon,
    HelpCircleIcon,
    EditIcon,
    Trash2Icon,
    PlusIcon,
    DownloadIcon,
    ShieldAlertIcon,
    CheckCircle2Icon,
    XCircleIcon,
    AlertTriangleIcon,
    FolderIcon,
    ImageIcon,
    PaperclipIcon,
    CalendarIcon,
} from '@lucide/vue';

const ICONS = {
    LayoutDashboardIcon,
    FileTextIcon,
    FilePlusIcon,
    UserIcon,
    ShieldCheckIcon,
    SettingsIcon,
    UsersIcon,
    TagIcon,
    BellIcon,
    SearchIcon,
    LogOutIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    MenuIcon,
    ArrowRightIcon,
    TrendingUpIcon,
    PieChartIcon,
    ClockIcon,
    MailIcon,
    PhoneIcon,
    HelpCircleIcon,
    EditIcon,
    Trash2Icon,
    PlusIcon,
    DownloadIcon,
    ShieldAlertIcon,
    CheckCircle2Icon,
    XCircleIcon,
    AlertTriangleIcon,
    FolderIcon,
    ImageIcon,
    PaperclipIcon,
    CalendarIcon,
};

const props = defineProps({
    name: { type: String, required: true },
});

const icon = computed(() => ICONS[props.name] || ICONS.HelpCircleIcon);
</script>
