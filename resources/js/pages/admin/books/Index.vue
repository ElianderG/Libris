<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/Admin/BookController';

defineProps<{
    search: string;
    books: Array<{
        id: number;
        title: string;
        author: string;
        category: string;
        price: string;
        is_published: boolean;
        edit_url: string;
    }>;
}>();
</script>

<template>
    <Head title="Livros" />

    <section>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Administração</p>
                <h1 class="mt-2 font-serif text-4xl text-foreground">Livros</h1>
            </div>
            <Link
                :href="BookController.create.url()"
                class="inline-flex h-10 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground"
            >
                Novo livro
            </Link>
        </div>

        <form :action="BookController.index.url()" method="get" class="mt-8 flex flex-wrap items-center gap-3">
            <label class="sr-only" for="busca-titulo">Buscar por título</label>
            <input
                id="busca-titulo"
                name="q"
                type="search"
                :value="search"
                placeholder="Buscar por título"
                class="h-10 min-w-0 flex-1 rounded-full border border-input bg-card px-4 text-sm text-foreground"
            />
            <button type="submit" class="h-10 rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground">
                Buscar
            </button>
        </form>

        <p v-if="books.length === 0" class="mt-8 text-base leading-7 text-foreground">
            {{ search ? 'Nenhum livro com esse título.' : 'Nenhum livro ainda.' }}
        </p>
        <ul v-else class="mt-6 divide-y divide-border">
            <li v-for="book in books" :key="book.id" class="flex flex-wrap items-baseline justify-between gap-3 py-4">
                <div class="min-w-0">
                    <Link :href="book.edit_url" class="font-serif text-2xl break-words text-foreground hover:text-primary">
                        {{ book.title }}
                    </Link>
                    <p class="mt-1 text-sm text-foreground/80">
                        {{ book.author }} · {{ book.category }} · {{ book.price }}
                    </p>
                    <p class="mt-1 text-sm" :class="book.is_published ? 'text-emerald-700 dark:text-emerald-300' : 'text-foreground/70'">
                        {{ book.is_published ? 'Publicado' : 'Rascunho' }}
                    </p>
                </div>
                <Link :href="book.edit_url" class="text-sm text-foreground underline-offset-4 hover:underline">
                    Editar
                </Link>
            </li>
        </ul>
    </section>
</template>
