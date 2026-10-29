<template>
    <Card :padded="false" :bordered="true" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-surface-border bg-surface/50">
                        <th
                            v-for="(key, index) in columns"
                            :key="index"
                            class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-text-secondary"
                        >
                            <slot :name="`header-${key}`" :column="key">
                                {{ columnLabel(key) }}
                            </slot>
                        </th>
                        <th
                            v-if="$slots.actions"
                            class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-text-secondary"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border bg-surface">
                    <template v-if="rows.length">
                        <tr
                            v-for="(row, rowIndex) in rows"
                            :key="rowKey(row, rowIndex)"
                            class="hover:bg-surface-elevated/50 transition-colors"
                        >
                            <td
                                v-for="(key, index) in columns"
                                :key="index"
                                class="px-6 py-4 text-sm text-text-main"
                            >
                                <slot
                                    :name="`cell-${key}`"
                                    :row="row"
                                    :column="key"
                                >
                                    {{ row[key] }}
                                </slot>
                            </td>
                            <td
                                v-if="$slots.actions"
                                class="px-6 py-4"
                            >
                                <slot name="actions" :row="row" />
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr>
                            <td
                                :colspan="columns.length + ($slots.actions ? 1 : 0)"
                                class="px-6 py-12 text-center"
                            >
                                <slot name="empty">
                                    <EmptyState title="No records found" description="Try adjusting your filters." />
                                </slot>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </Card>
</template>

<script setup>
import { computed } from 'vue';
import Card from './Card.vue';
import EmptyState from './EmptyState.vue';

const props = defineProps({
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    rowKey: { type: Function, default: (row, index) => index },
});

const columnLabel = (key) => {
    return String(key)
        .split(/(?=[A-Z])|_/)
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');
};
</script>
