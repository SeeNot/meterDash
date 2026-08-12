<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { dashboard } from '@/routes';
import plans from '@/routes/plans';

defineProps<{
    title: string;
    subtitle?: string;
}>();

const page = usePage();

const flash = computed(
    () => (page.props.flash as { success: string | null } | undefined)?.success,
);

const showFlash = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

watch(
    flash,
    (message) => {
        showFlash.value = Boolean(message);
        clearTimeout(timer);

        if (message) {
            timer = setTimeout(() => {
                showFlash.value = false;
            }, 4000);
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="min-h-screen bg-plane text-ink">
        <header class="border-b border-hairline bg-surface">
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-8 gap-y-3 px-6 py-4"
            >
                <span class="text-sm font-semibold tracking-tight">
                    meter<span class="text-series-1">Dash</span>
                </span>
                <nav class="flex items-center gap-6 text-sm">
                    <Link
                        :href="dashboard.url()"
                        class="text-ink-secondary transition-colors hover:text-ink"
                        >Dashboard</Link
                    >
                    <Link
                        :href="plans.index.url()"
                        class="text-ink-secondary transition-colors hover:text-ink"
                        >Plans</Link
                    >
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-10">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="subtitle" class="mt-1 text-sm text-ink-secondary">
                        {{ subtitle }}
                    </p>
                </div>
                <p
                    v-if="showFlash && flash"
                    class="rounded-md border border-hairline bg-surface px-3 py-1.5 text-sm text-good"
                    role="status"
                >
                    {{ flash }}
                </p>
            </div>

            <slot />
        </main>
    </div>
</template>
