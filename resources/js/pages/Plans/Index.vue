<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import planRoutes from '@/routes/plans';
import type { TariffPlan, TariffPlanType, TariffPlanTypeOption } from '@/types';

const props = defineProps<{
    plans: TariffPlan[];
    types: TariffPlanTypeOption[];
}>();

const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    type: 'fixed' as TariffPlanType,
    energy_rate_eur: '0.18',
    standing_charge_eur: '3.00',
    is_active: true,
});

const rateHint = computed(
    () =>
        props.types.find((type) => type.value === form.type)?.rateDescription ??
        '',
);

function reset(): void {
    editingId.value = null;
    form.clearErrors();
    form.defaults({
        name: '',
        type: 'fixed',
        energy_rate_eur: '0.18',
        standing_charge_eur: '3.00',
        is_active: true,
    });
    form.reset();
}

function startEdit(plan: TariffPlan): void {
    editingId.value = plan.id;
    form.clearErrors();
    form.name = plan.name;
    form.type = plan.type;
    form.energy_rate_eur = String(plan.energy_rate_eur);
    form.standing_charge_eur = String(plan.standing_charge_eur);
    form.is_active = plan.is_active;
}

function submit(): void {
    if (editingId.value === null) {
        form.post(planRoutes.store.url(), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });

        return;
    }

    form.put(planRoutes.update.url(editingId.value), {
        preserveScroll: true,
        onSuccess: () => reset(),
    });
}

function remove(id: number): void {
    router.delete(planRoutes.destroy.url(id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout
        title="Tariff plans"
        subtitle="Rates used to cost your consumption on the dashboard."
    >
        <Head title="Tariff plans" />

        <div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
            <form
                class="h-fit rounded-lg border border-hairline bg-surface p-4"
                @submit.prevent="submit"
            >
                <h2 class="text-sm font-semibold text-ink">
                    {{ editingId === null ? 'Add a plan' : 'Edit plan' }}
                </h2>

                <div class="mt-4 space-y-3">
                    <label class="block">
                        <span class="text-xs text-ink-muted">Name</span>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                        />
                        <span
                            v-if="form.errors.name"
                            class="mt-1 block text-xs text-critical"
                            >{{ form.errors.name }}</span
                        >
                    </label>

                    <label class="block">
                        <span class="text-xs text-ink-muted">Type</span>
                        <select
                            v-model="form.type"
                            class="mt-1 w-full rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                        >
                            <option
                                v-for="type in types"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </select>
                        <span class="mt-1 block text-xs text-ink-muted">{{
                            rateHint
                        }}</span>
                    </label>

                    <label class="block">
                        <span class="text-xs text-ink-muted"
                            >{{
                                form.type === 'dynamic'
                                    ? 'Margin over market'
                                    : 'Price'
                            }}
                            (€/kWh)</span
                        >
                        <input
                            v-model="form.energy_rate_eur"
                            type="number"
                            step="0.00001"
                            min="0"
                            required
                            class="mt-1 w-full rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                        />
                        <span
                            v-if="form.errors.energy_rate_eur"
                            class="mt-1 block text-xs text-critical"
                            >{{ form.errors.energy_rate_eur }}</span
                        >
                    </label>

                    <label class="block">
                        <span class="text-xs text-ink-muted"
                            >Standing charge (€/month)</span
                        >
                        <input
                            v-model="form.standing_charge_eur"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            class="mt-1 w-full rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                        />
                        <span
                            v-if="form.errors.standing_charge_eur"
                            class="mt-1 block text-xs text-critical"
                            >{{ form.errors.standing_charge_eur }}</span
                        >
                    </label>

                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input v-model="form.is_active" type="checkbox" />
                        Include in comparisons
                    </label>
                </div>

                <div class="mt-4 flex gap-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 rounded-md bg-series-1 px-3 py-2 text-sm font-medium text-white disabled:opacity-50"
                    >
                        {{ editingId === null ? 'Add plan' : 'Save changes' }}
                    </button>
                    <button
                        v-if="editingId !== null"
                        type="button"
                        class="rounded-md border border-hairline px-3 py-2 text-sm text-ink-secondary"
                        @click="reset"
                    >
                        Cancel
                    </button>
                </div>
            </form>

            <div
                class="overflow-x-auto rounded-lg border border-hairline bg-surface"
            >
                <table class="w-full min-w-[34rem] text-left text-sm">
                    <thead
                        class="border-b border-hairline text-xs tracking-wide text-ink-muted uppercase"
                    >
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Plan
                            </th>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Type
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right font-medium"
                            >
                                Rate
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right font-medium"
                            >
                                Monthly
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right font-medium"
                            >
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="tabular-nums">
                        <tr
                            v-for="plan in plans"
                            :key="plan.id"
                            class="border-b border-hairline last:border-0"
                        >
                            <th
                                scope="row"
                                class="px-4 py-3 text-left font-medium"
                                :class="
                                    plan.is_active
                                        ? 'text-ink'
                                        : 'text-ink-muted'
                                "
                            >
                                {{ plan.name }}
                                <span
                                    v-if="!plan.is_active"
                                    class="ml-1 text-xs font-normal"
                                    >(excluded)</span
                                >
                            </th>
                            <td class="px-4 py-3 text-ink-secondary">
                                {{ plan.typeLabel }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink-secondary">
                                {{ plan.energy_rate_eur.toFixed(4) }}
                            </td>
                            <td class="px-4 py-3 text-right text-ink-secondary">
                                {{ plan.standing_charge_eur.toFixed(2) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    class="text-xs text-ink-secondary hover:text-ink"
                                    @click="startEdit(plan)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="ml-3 text-xs text-ink-secondary hover:text-critical"
                                    @click="remove(plan.id)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
