<template>
    <label class="theme-control" aria-label="Choose color theme">
        <svg class="theme-control__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path v-if="theme === 'dark'" d="M20.2 15.7A8.5 8.5 0 0 1 8.3 3.8 8.5 8.5 0 1 0 20.2 15.7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            <template v-else>
                <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8" />
                <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </template>
        </svg>
        <span class="theme-control__label">Theme</span>
        <select
            v-model="selectedPreference"
            class="theme-control__select"
            aria-label="Color theme"
            @change="updateTheme"
        >
            <option value="light">Light</option>
            <option value="dark">Dark</option>
            <option value="system">System</option>
        </select>
        <svg class="theme-control__chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </label>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { getThemePreference, setThemePreference } from '../theme';

const selectedPreference = ref(getThemePreference());
const theme = ref(document.documentElement.dataset.theme || 'light');

function updateTheme() {
    setThemePreference(selectedPreference.value);
}

function syncTheme(event) {
    selectedPreference.value = event.detail.preference;
    theme.value = event.detail.theme;
}

onMounted(() => window.addEventListener('career-next-theme-change', syncTheme));
onBeforeUnmount(() => window.removeEventListener('career-next-theme-change', syncTheme));
</script>
