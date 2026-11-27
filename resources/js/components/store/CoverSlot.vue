<script setup lang="ts">
defineProps<{
    tone?: 'ink' | 'wine' | 'sand' | 'moss';
    label?: string;
}>();
</script>

<template>
    <div
        class="relative aspect-[3/4] overflow-hidden rounded-lg shadow-[0_12px_30px_-18px_rgba(40,20,10,0.55)] ring-1 ring-black/10"
        :class="{
            'bg-[oklch(0.32_0.04_40)] text-[oklch(0.93_0.02_85)]': tone === 'ink' || !tone,
            'bg-[oklch(0.42_0.11_28)] text-[oklch(0.96_0.02_85)]': tone === 'wine',
            'bg-[oklch(0.86_0.04_80)] text-[oklch(0.28_0.04_40)]': tone === 'sand',
            'bg-[oklch(0.38_0.05_155)] text-[oklch(0.95_0.02_85)]': tone === 'moss',
        }"
    >
        <div class="absolute inset-y-0 left-0 w-2 bg-black/15" />
        <div class="flex h-full flex-col justify-between p-4 pl-5">
            <span class="text-[0.65rem] font-medium tracking-[0.14em] uppercase">Libris te liberant</span>
            <span class="font-serif text-lg leading-tight break-words">{{ label || 'Capa reservada' }}</span>
        </div>
    </div>
</template>
