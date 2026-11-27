<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CoverSlot from '@/components/store/CoverSlot.vue';
import { show } from '@/routes/books';

export type CatalogBook = {
    slug: string;
    title: string;
    author: string;
    category: string;
    price: string;
    cover_url: string | null;
};

defineProps<{
    book: CatalogBook;
}>();
</script>

<template>
    <article class="min-w-0">
        <Link :href="show(book.slug)" class="block min-w-0 rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-primary">
            <img
                v-if="book.cover_url"
                :src="book.cover_url"
                :alt="`Capa de ${book.title}`"
                class="aspect-[3/4] w-full rounded-lg object-cover ring-1 ring-black/10"
            />
            <CoverSlot v-else :label="book.title" />
            <h3 class="mt-3 font-serif text-lg leading-snug break-words text-foreground">{{ book.title }}</h3>
            <p class="mt-1 text-sm break-words text-foreground/80">{{ book.author }}</p>
            <p class="mt-1 text-sm break-words text-foreground/70">{{ book.category }}</p>
            <p class="mt-2 text-sm font-medium break-words text-foreground">{{ book.price }}</p>
        </Link>
    </article>
</template>
