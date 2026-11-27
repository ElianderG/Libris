<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { catalog } from '@/routes';

defineProps<{
    books: Array<{
        title: string;
        author: string;
        slug: string;
        read_url: string;
    }>;
}>();
</script>

<template>
    <Head title="Biblioteca" />

    <section class="py-8">
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Sua conta</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Biblioteca</h1>

        <div v-if="books.length === 0" class="mt-6">
            <p class="max-w-xl text-base leading-7 text-foreground">
                Os livros que você comprar aparecem aqui, prontos para ler na plataforma.
            </p>
            <Link
                :href="catalog()"
                class="mt-6 inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground"
            >
                Ver o catálogo
            </Link>
        </div>

        <ul v-else class="mt-8 divide-y divide-border">
            <li v-for="book in books" :key="book.slug" class="py-5">
                <Link :href="book.read_url" class="font-serif text-2xl break-words text-foreground hover:text-primary">
                    {{ book.title }}
                </Link>
                <p class="mt-1 text-sm text-foreground">{{ book.author }}</p>
            </li>
        </ul>
    </section>
</template>
