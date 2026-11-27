<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CoverSlot from '@/components/store/CoverSlot.vue';
import type { CatalogBook } from '@/components/store/BookCard.vue';

defineProps<{
    book: CatalogBook & {
        description: string;
        preview: string;
    };
}>();
</script>

<template>
    <Head :title="book.title" />

    <article class="py-6">
        <div class="grid items-start gap-8 md:grid-cols-[16rem_1fr]">
            <img
                v-if="book.cover_url"
                :src="book.cover_url"
                :alt="`Capa de ${book.title}`"
                class="aspect-[3/4] w-full max-w-xs rounded-lg object-cover ring-1 ring-black/10"
            />
            <CoverSlot v-else :label="book.title" class="max-w-xs" />

            <div class="min-w-0">
                <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">{{ book.category }}</p>
                <h1 class="mt-2 font-serif text-4xl leading-tight break-words text-foreground">{{ book.title }}</h1>
                <p class="mt-3 text-base break-words text-foreground">{{ book.author }}</p>
                <p class="mt-4 text-lg font-medium text-foreground">{{ book.price }}</p>
                <p class="mt-4 max-w-xl text-base leading-7 break-words text-foreground">{{ book.description }}</p>
                <button
                    type="button"
                    disabled
                    class="mt-6 inline-flex h-11 cursor-not-allowed items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground opacity-60"
                >
                    Adicionar à biblioteca
                </button>
                <p class="mt-2 text-sm text-foreground/80">A sacola abre no próximo passo.</p>
            </div>
        </div>

        <section class="mt-10 max-w-2xl border-t border-border pt-8">
            <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Prévia</p>
            <div class="mt-4 space-y-4">
                <p
                    v-for="(paragraph, index) in book.preview.split(/\n\s*\n/)"
                    :key="index"
                    class="text-base leading-7 break-words text-foreground"
                >
                    {{ paragraph }}
                </p>
            </div>
        </section>
    </article>
</template>
