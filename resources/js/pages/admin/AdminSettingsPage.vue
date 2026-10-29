<template>
    <AppShell section="admin" title="System settings" subtitle="Control system-wide notification and template preferences.">
        <Card padded bordered>
            <div v-if="settingsLoading" class="py-8 text-center">
                <Loading variant="page" label="Loading settings…" />
            </div>

            <div v-else-if="groupedSettings.length" class="space-y-6">
                <div
                    v-for="group in groupedSettings"
                    :key="group.group"
                    class="space-y-4"
                >
                    <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider border-b border-surface-border pb-2">
                        {{ group.group.charAt(0).toUpperCase() + group.group.slice(1) }}
                    </h3>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="setting in group.items"
                            :key="setting.id"
                            class="flex flex-col gap-2 p-4 rounded-xl border border-surface-border bg-surface"
                        >
                            <label class="text-xs font-medium text-text-main">{{ setting.label }}</label>
                            <p class="text-[10px] text-text-muted">{{ setting.key }}</p>
                            <component
                                :is="setting.type === 'textarea' ? Textarea : Input"
                                v-model="settingValues[setting.id]"
                                name="setting-value"
                                :rows="setting.type === 'textarea' ? 2 : undefined"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <Button variant="primary" :loading="saving" @click="saveSettings">Save all settings</Button>
                </div>
            </div>

            <div v-else class="py-8 text-center">
                <EmptyState title="No settings available" description="There are no system settings configured." iconColor="text-text-muted" />
            </div>
        </Card>
    </AppShell>
</template>

<script setup>
import { onMounted, ref, reactive, computed } from 'vue';
import axios from 'axios';
import { useToastStore } from '../../stores/toasts';
import { extractMessage, normalizeItems } from '../../utils/http';
import AppShell from '../../layouts/AppShell.vue';
import Card from '../../components/Card.vue';
import Input from '../../components/Input.vue';
import Textarea from '../../components/Textarea.vue';
import Button from '../../components/Button.vue';
import Loading from '../../components/Loading.vue';
import EmptyState from '../../components/EmptyState.vue';

const toast = useToastStore();
const settings = ref([]);
const settingValues = ref({});
const settingsLoading = ref(false);
const saving = ref(false);

const groupedSettings = computed(() => {
    const groups = {};
    settings.value.forEach((s) => {
        if (!groups[s.group]) groups[s.group] = [];
        groups[s.group].push(s);
    });
    return Object.entries(groups).map(([group, items]) => ({ group, items }));
});

async function load() {
    settingsLoading.value = true;
    try {
        const { data } = await axios.get('/api/admin/settings');
        const items = normalizeItems(data);
        settings.value = items;
        settingValues.value = Object.fromEntries(
            items.map((s) => [s.id, s.value?.body ?? s.value ?? ''])
        );
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load settings.'));
    } finally {
        settingsLoading.value = false;
    }
}

async function saveSettings() {
    saving.value = true;
    try {
        const payload = { settings: {} };
        for (const [id, value] of Object.entries(settingValues.value)) {
            payload.settings[id] = { value };
        }
        const { data } = await axios.put('/api/admin/settings', payload);
        toast.success(data.message || 'Settings saved successfully.');
        settings.value = normalizeItems(data.data);
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to save settings.'));
    } finally {
        saving.value = false;
    }
}

onMounted(load);
</script>
