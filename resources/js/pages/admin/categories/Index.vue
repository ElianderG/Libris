<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';

defineProps<{
    categories: Array<{
        id: number;
        name: string;
        books: number;
        edit_url: string;
    }>;
}>();
</script>

<template>
    <Head title="Categorias" />

    <section>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Administração</p>
                <h1 class="mt-2 font-serif text-4xl text-foreground">Categorias</h1>
            </div>
            <Link
                :href="CategoryController.create.url()"
                class="inline-flex h-10 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground"
            >
                Nova categoria
            </Link>
        </div>

        <p v-if="categories.length === 0" class="mt-8 text-base leading-7 text-foreground">
            Nenhuma categoria ainda.
        </p>
        <ul v-else class="mt-8 divide-y divide-border">
            <li v-for="category in categories" :key="category.id" class="flex flex-wrap items-baseline justify-between gap-3 py-4">
                <div>
                    <Link :href="category.edit_url" class="font-serif text-2xl text-foreground hover:text-primary">
                        {{ category.name }}
                    </Link>
                    <p class="mt-1 text-sm text-foreground/80">
                        {{ category.books === 1 ? '1 livro' : `${category.books} livros` }}
                    </p>
                </div>
                <Link :href="category.edit_url" class="text-sm text-foreground underline-offset-4 hover:underline">
                    Editar
                </Link>
            </li>
        </ul>
    </section>
</template>
