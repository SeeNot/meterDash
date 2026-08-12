<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { date, kwh } from '@/lib/format';
import entryRoutes from '@/routes/entries';
import type { WattageEntry } from '@/types';

const props = defineProps<{
    entries: WattageEntry[];
}>();

type Row = WattageEntry & {
    deltaKwh: number | null;
    days: number | null;
    kwhPerDay: number | null;
};

/**
 * Entries arrive newest first, so each row's consumption is measured against
 * the row below it. The oldest reading has no predecessor and stays blank.
 */
const rows = computed<Row[]>(() =>
    props.entries.map((entry, index) => {
        const previous = props.entries[index + 1];

        if (previous === undefined) {
            return { ...entry, deltaKwh: null, days: null, kwhPerDay: null };
        }

        const days = Math.round(
            (Date.parse(entry.measured_at) - Date.parse(previous.measured_at)) /
                86_400_000,
        );
        const deltaKwh = entry.reading_kwh - previous.reading_kwh;

        return {
            ...entry,
            deltaKwh,
            days,
            kwhPerDay: days > 0 ? deltaKwh / days : null,
        };
    }),
);

const editingId = ref<number | null>(null);

const form = useForm({
    measured_at: '',
    reading_kwh: '',
    note: '',
});

function startEdit(entry: WattageEntry): void {
    editingId.value = entry.id;
    form.clearErrors();
    form.measured_at = entry.measured_at;
    form.reading_kwh = String(entry.reading_kwh);
    form.note = entry.note ?? '';
}

function cancelEdit(): void {
    editingId.value = null;
    form.clearErrors();
}

function saveEdit(id: number): void {
    form.put(entryRoutes.update.url(id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function remove(id: number): void {
    router.delete(entryRoutes.destroy.url(id), { preserveScroll: true });
}
</script>

<template>
    <div class="overflow-x-auto rounded-lg border border-hairline bg-surface">
        <table class="w-full min-w-[36rem] text-left text-sm">
            <caption class="sr-only">
                Recorded meter readings with the consumption derived from each
                pair
            </caption>
            <thead
                class="border-b border-hairline text-xs tracking-wide text-ink-muted uppercase"
            >
                <tr>
                    <th scope="col" class="px-4 py-3 font-medium">Date</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">
                        Reading
                    </th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">
                        Used
                    </th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">
                        Per day
                    </th>
                    <th scope="col" class="px-4 py-3 font-medium">Note</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="tabular-nums">
                <template v-for="row in rows" :key="row.id">
                    <tr
                        v-if="editingId === row.id"
                        class="border-b border-hairline"
                    >
                        <td colspan="6" class="px-4 py-3">
                            <div class="flex flex-wrap items-end gap-2">
                                <label
                                    class="flex flex-col gap-1 text-xs text-ink-muted"
                                >
                                    Date
                                    <input
                                        v-model="form.measured_at"
                                        type="date"
                                        class="rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                                    />
                                </label>
                                <label
                                    class="flex flex-col gap-1 text-xs text-ink-muted"
                                >
                                    Reading
                                    <input
                                        v-model="form.reading_kwh"
                                        type="number"
                                        step="0.001"
                                        class="rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                                    />
                                </label>
                                <label
                                    class="flex flex-1 flex-col gap-1 text-xs text-ink-muted"
                                >
                                    Note
                                    <input
                                        v-model="form.note"
                                        type="text"
                                        class="rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                                    />
                                </label>
                                <button
                                    type="button"
                                    class="rounded-md bg-series-1 px-3 py-1.5 text-sm font-medium text-white"
                                    :disabled="form.processing"
                                    @click="saveEdit(row.id)"
                                >
                                    Save
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md border border-hairline px-3 py-1.5 text-sm text-ink-secondary"
                                    @click="cancelEdit"
                                >
                                    Cancel
                                </button>
                            </div>
                            <p
                                v-for="(error, field) in form.errors"
                                :key="field"
                                class="mt-2 text-xs text-critical"
                            >
                                {{ error }}
                            </p>
                        </td>
                    </tr>
                    <tr v-else class="border-b border-hairline last:border-0">
                        <td class="px-4 py-3 text-ink">
                            {{ date(row.measured_at) }}
                        </td>
                        <td class="px-4 py-3 text-right text-ink">
                            {{ kwh(row.reading_kwh, 1) }}
                        </td>
                        <td class="px-4 py-3 text-right text-ink-secondary">
                            {{
                                row.deltaKwh === null ? '—' : kwh(row.deltaKwh)
                            }}
                        </td>
                        <td class="px-4 py-3 text-right text-ink-secondary">
                            {{
                                row.kwhPerDay === null
                                    ? '—'
                                    : `${kwh(row.kwhPerDay, 2)}`
                            }}
                        </td>
                        <td class="px-4 py-3 text-ink-muted">
                            {{ row.note ?? '' }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button
                                type="button"
                                class="text-xs text-ink-secondary hover:text-ink"
                                @click="startEdit(row)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="ml-3 text-xs text-ink-secondary hover:text-critical"
                                @click="remove(row.id)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>
