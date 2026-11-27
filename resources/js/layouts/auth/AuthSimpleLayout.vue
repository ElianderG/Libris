<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="mx-auto w-full max-w-md py-4">
        <div v-if="title || description" class="mb-6 text-center">
            <h1 v-if="title" class="font-serif text-4xl text-foreground">{{ title }}</h1>
            <p v-if="description" class="mt-2 text-sm leading-6 text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <slot />
    </div>
</template>
