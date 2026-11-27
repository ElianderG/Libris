<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BookCard, { type CatalogBook } from '@/components/store/BookCard.vue';
import CoverSlot from '@/components/store/CoverSlot.vue';
import { catalog } from '@/routes';

defineProps<{
    featured: CatalogBook[];
    recent: CatalogBook[];
}>();
</script>

<template>
    <Head title="Início" />

    <section class="grid items-center gap-10 py-6 lg:grid-cols-[1.15fr_0.85fr] lg:py-10">
        <div>
            <p class="text-xs font-medium tracking-[0.18em] text-primary uppercase">
                Sua livraria digital
            </p>
            <h1 class="mt-3 max-w-xl font-serif text-4xl leading-tight text-foreground sm:text-5xl">
                Histórias que merecem um lugar na sua estante.
            </h1>
            <p class="mt-4 max-w-lg text-base leading-7 text-foreground">
                Descubra novos livros, escolha suas próximas leituras e tenha tudo sempre à mão. Compre seus títulos favoritos e leia diretamente pela plataforma.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <Link
                    :href="catalog()"
                    class="inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground"
                >
                    Explorar o catálogo
                </Link>
                <a
                    href="#como-funciona"
                    class="inline-flex h-11 items-center rounded-full border border-border bg-card px-5 text-sm font-medium text-foreground"
                >
                    Conheça a plataforma
                </a>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3 sm:gap-4">
            <CoverSlot tone="wine" class="translate-y-6" />
            <CoverSlot tone="ink" />
            <CoverSlot tone="moss" class="translate-y-8" />
        </div>
    </section>

    <section class="mt-8 border-t border-border pt-8">
        <p class="text-xs font-medium tracking-[0.16em] text-foreground uppercase">Em destaque</p>
        <h2 class="mt-2 font-serif text-3xl text-foreground">Livros para começar</h2>
        <div v-if="featured.length" class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <BookCard v-for="book in featured" :key="book.slug" :book="book" />
        </div>
        <p v-else class="mt-3 max-w-2xl text-sm leading-6 text-foreground">
            Os livros em destaque aparecem aqui quando forem publicados.
        </p>
    </section>

    <section v-if="recent.length" class="mt-12">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-medium tracking-[0.16em] text-foreground uppercase">Catálogo</p>
                <h2 class="mt-2 font-serif text-3xl text-foreground">Também na estante</h2>
            </div>
            <Link :href="catalog()" class="text-sm font-medium text-primary">Ver todos</Link>
        </div>
        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <BookCard v-for="book in recent" :key="book.slug" :book="book" />
        </div>
    </section>

    <section id="como-funciona" class="mt-14 grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-border bg-card p-5">
            <p class="font-serif text-2xl text-primary">01</p>
            <h3 class="mt-3 font-medium text-foreground">Encontre sua próxima leitura</h3>
            <p class="mt-2 text-sm leading-6 text-foreground">Explore o catálogo, conheça os títulos disponíveis e confira informações sobre cada livro antes de comprar.</p>
        </article>
        <article class="rounded-2xl border border-border bg-card p-5">
            <p class="font-serif text-2xl text-primary">02</p>
            <h3 class="mt-3 font-medium text-foreground">Compre com facilidade</h3>
            <p class="mt-2 text-sm leading-6 text-foreground">Finalize sua compra utilizando Pix, com praticidade e segurança. O pagamento atual está disponível em ambiente de testes.</p>
        </article>
        <article class="rounded-2xl border border-border bg-card p-5">
            <p class="font-serif text-2xl text-primary">03</p>
            <h3 class="mt-3 font-medium text-foreground">Tenha seus livros sempre à mão</h3>
            <p class="mt-2 text-sm leading-6 text-foreground">Após a confirmação da compra, seu livro estará disponível na sua biblioteca para leitura diretamente pela plataforma.</p>
        </article>
    </section>
</template>
