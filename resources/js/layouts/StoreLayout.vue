<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import FlashBanner from '@/components/store/FlashBanner.vue';
import StoreFooter from '@/components/store/StoreFooter.vue';
import StoreHeader from '@/components/store/StoreHeader.vue';

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <StoreHeader />
        <FlashBanner />
        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
            <slot />
        </main>
        <StoreFooter />
        <Toaster />
    </div>
</template>
