<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import entries from '@/routes/entries';
import type { WattageEntry } from '@/types';

const props = defineProps<{
    latest: WattageEntry | null;
}>();

const form = useForm({
    measured_at: new Date().toISOString().slice(0, 10),
    reading_kwh: '',
    note: '',
});

function submit(): void {
    form.post(entries.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('reading_kwh', 'note');
        },
    });
}
</script>

<template>
    <form
        class="rounded-lg border border-hairline bg-surface p-4"
        @submit.prevent="submit"
    >
        <h2 class="text-sm font-semibold text-ink">Add a reading</h2>
        <p class="mt-1 text-xs text-ink-secondary">
            <template v-if="props.latest">
                Last recorded {{ props.latest.reading_kwh }} kWh on
                {{ props.latest.measured_at }}.
            </template>
            <template v-else>
                Enter the cumulative total shown on your meter.
            </template>
        </p>

        <div class="mt-4 space-y-3">
            <label class="block">
                <span class="text-xs text-ink-muted">Date</span>
                <input
                    v-model="form.measured_at"
                    type="date"
                    required
                    class="mt-1 w-full rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                />
                <span
                    v-if="form.errors.measured_at"
                    class="mt-1 block text-xs text-critical"
                    >{{ form.errors.measured_at }}</span
                >
            </label>

            <label class="block">
                <span class="text-xs text-ink-muted">Meter reading (kWh)</span>
                <input
                    v-model="form.reading_kwh"
                    type="number"
                    step="0.001"
                    min="0"
                    required
                    placeholder="45210.5"
                    class="mt-1 w-full rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                />
                <span
                    v-if="form.errors.reading_kwh"
                    class="mt-1 block text-xs text-critical"
                    >{{ form.errors.reading_kwh }}</span
                >
            </label>

            <label class="block">
                <span class="text-xs text-ink-muted">Note (optional)</span>
                <input
                    v-model="form.note"
                    type="text"
                    maxlength="255"
                    class="mt-1 w-full rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                />
                <span
                    v-if="form.errors.note"
                    class="mt-1 block text-xs text-critical"
                    >{{ form.errors.note }}</span
                >
            </label>
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="mt-4 w-full rounded-md bg-series-1 px-3 py-2 text-sm font-medium text-white transition-opacity disabled:opacity-50"
        >
            {{ form.processing ? 'Saving…' : 'Save reading' }}
        </button>
    </form>
</template>
