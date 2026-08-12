<script setup lang="ts">
defineProps<{
    title: string;
    message: string;
}>();
</script>

<template>
    <div
        class="flex h-full min-h-40 flex-col items-center justify-center rounded-lg border border-dashed border-baseline p-8 text-center"
    >
        <p class="text-sm font-medium text-ink">{{ title }}</p>
        <p class="mt-1 max-w-sm text-sm text-ink-secondary">{{ message }}</p>
    </div>
</template>
