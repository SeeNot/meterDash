<script setup lang="ts">
defineProps<{
    label: string;
    value: string;
    unit?: string;
    hint?: string;
}>();
</script>

<template>
    <div class="rounded-lg border border-hairline bg-surface p-4">
        <p class="text-xs font-medium tracking-wide text-ink-muted uppercase">
            {{ label }}
        </p>
        <p class="mt-2 text-2xl font-semibold text-ink">
            {{ value }}
            <span v-if="unit" class="text-sm font-normal text-ink-secondary">{{
                unit
            }}</span>
        </p>
        <p v-if="hint" class="mt-1 text-xs text-ink-muted">{{ hint }}</p>
    </div>
</template>
