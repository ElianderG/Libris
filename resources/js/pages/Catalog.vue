<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BookCard, { type CatalogBook } from '@/components/store/BookCard.vue';
import { catalog } from '@/routes';

type PageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

defineProps<{
    products: {
        data: CatalogBook[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        links: PageLink[];
    };
    filters: {
        categoria: string;
        q: string;
        ordem: string;
    };
    categories: Array<{
        name: string;
        slug: string;
    }>;
}>();
</script>

<template>
    <Head title="Catálogo" />

    <section class="py-6">
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Estante</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Catálogo</h1>
        <p class="mt-3 max-w-xl text-base leading-7 text-foreground">
            Livros publicados, com capa, autor e preço. A busca e os filtros ficam no endereço da página.
        </p>

        <form :action="catalog.url()" method="get" class="mt-6 grid gap-3 sm:grid-cols-[1fr_1.4fr_1fr_auto] sm:items-end">
            <label class="grid gap-1 text-sm text-foreground">
                Categoria
                <select
                    name="categoria"
                    :value="filters.categoria"
                    class="h-10 rounded-full border border-input bg-card px-4 text-sm text-foreground"
                >
                    <option value="">Todas</option>
                    <option v-for="category in categories" :key="category.slug" :value="category.slug">
                        {{ category.name }}
                    </option>
                </select>
            </label>
            <label class="grid gap-1 text-sm text-foreground">
                Título ou autor
                <input
                    name="q"
                    type="search"
                    :value="filters.q"
                    placeholder="Buscar"
                    class="h-10 rounded-full border border-input bg-card px-4 text-sm text-foreground"
                />
            </label>
            <label class="grid gap-1 text-sm text-foreground">
                Ordem
                <select
                    name="ordem"
                    :value="filters.ordem"
                    class="h-10 rounded-full border border-input bg-card px-4 text-sm text-foreground"
                >
                    <option value="recente">Mais recente</option>
                    <option value="preco">Menor preço</option>
                </select>
            </label>
            <button type="submit" class="h-10 rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground">
                Filtrar
            </button>
        </form>

        <p v-if="products.data.length === 0" class="mt-10 text-base text-foreground">
            Nenhum livro encontrado para essa busca.
        </p>

        <div v-else class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <BookCard v-for="book in products.data" :key="book.slug" :book="book" />
        </div>

        <nav v-if="products.last_page > 1" class="mt-10 flex flex-wrap items-center gap-3 text-sm" aria-label="Páginas do catálogo">
            <Link
                v-if="products.prev_page_url"
                :href="products.prev_page_url"
                class="rounded-full border border-border px-4 py-2 text-foreground"
            >
                Anterior
            </Link>
            <span class="text-foreground">Página {{ products.current_page }} de {{ products.last_page }}</span>
            <Link
                v-if="products.next_page_url"
                :href="products.next_page_url"
                class="rounded-full border border-border px-4 py-2 text-foreground"
            >
                Próxima
            </Link>
        </nav>
    </section>
</template>
