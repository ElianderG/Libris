
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import BookCard, { type CatalogBook } from '@/components/store/BookCard.vue';
import CoverSlot from '@/components/store/CoverSlot.vue';
import { catalog } from '@/routes';
import { show } from '@/routes/books';

const props = defineProps<{
    featured: CatalogBook[];
    recent: CatalogBook[];
}>();

const spotlight = computed(() => props.featured[0] ?? null);
const featuredRest = computed(() => (props.featured.length > 1 ? props.featured.slice(1) : []));
</script>

<template>
    <Head title="Início" />

    <section class="grid items-center gap-12 py-4 lg:grid-cols-[minmax(0,1.12fr)_minmax(0,0.88fr)] lg:gap-14 lg:py-8">
        <div class="min-w-0">
            <p class="flex items-center gap-3 text-[0.7rem] font-medium tracking-[0.22em] text-primary uppercase">
                <span class="h-px w-8 shrink-0 bg-primary" aria-hidden="true" />
                Sua próxima história começa aqui
            </p>
            <h1 class="mt-5 max-w-xl font-serif text-4xl leading-[1.08] tracking-tight text-balance text-foreground sm:text-5xl lg:text-6xl">
                Histórias que merecem um lugar na sua <span class="italic text-primary">estante</span>.
            </h1>
            <p class="mt-5 max-w-lg text-base leading-7 text-pretty text-foreground/80">
                Encontre histórias que despertam sua curiosidade, descubra novos autores e escolha sua próxima leitura. Compre seus livros e tenha acesso a eles diretamente pela plataforma.
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <Link
                    :href="catalog()"
                    class="inline-flex h-12 items-center justify-center rounded-full bg-primary px-6 text-sm font-medium text-primary-foreground shadow-[0_14px_30px_-16px_rgba(70,24,24,0.7)] transition-colors hover:bg-primary/90"
                >
                    Descobrir livros
                </Link>
                <a
                    href="#como-funciona"
                    class="inline-flex h-12 items-center justify-center rounded-full border border-border bg-card px-6 text-sm font-medium text-foreground transition-colors hover:border-primary/40 hover:bg-secondary"
                >
                    Como funciona
                </a>
            </div>
            <p class="mt-10 max-w-lg border-t border-border pt-5 text-sm leading-6 text-foreground/75">
                Leitura diretamente na plataforma
                <span class="mx-1.5 text-primary" aria-hidden="true">·</span>
                Pagamento via Pix
                <span class="mx-1.5 text-primary" aria-hidden="true">·</span>
                Biblioteca digital pessoal
            </p>
        </div>

        <div class="min-w-0 rounded-[1.75rem] border border-border/80 bg-card px-6 py-8 shadow-[0_28px_60px_-42px_rgba(40,20,10,0.55)] sm:px-8 sm:py-10">
            <Link
                v-if="spotlight"
                :href="show(spotlight.slug)"
                class="group mx-auto block max-w-[16.5rem] rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
                <img
                    v-if="spotlight.cover_url"
                    :src="spotlight.cover_url"
                    :alt="`Capa de ${spotlight.title}`"
                    class="aspect-[3/4] w-full rounded-lg object-cover shadow-[0_24px_44px_-22px_rgba(40,20,10,0.65)] ring-1 ring-black/10 transition-transform duration-300 group-hover:-translate-y-1"
                />
                <CoverSlot
                    v-else
                    :label="spotlight.title"
                    tone="wine"
                    class="shadow-[0_24px_44px_-22px_rgba(40,20,10,0.65)] transition-transform duration-300 group-hover:-translate-y-1"
                />
                <p class="mt-6 text-center text-[0.68rem] font-medium tracking-[0.2em] text-primary uppercase">
                    {{ spotlight.category }}
                </p>
                <p class="mt-2 text-center font-serif text-2xl leading-tight break-words text-foreground">
                    {{ spotlight.title }}
                </p>
                <p class="mt-1 text-center text-sm break-words text-foreground/75">{{ spotlight.author }}</p>
                <p class="mt-3 text-center text-sm font-medium text-foreground">{{ spotlight.price }}</p>
            </Link>
            <div v-else class="grid grid-cols-3 items-end gap-3">
                <CoverSlot tone="wine" class="translate-y-5" />
                <CoverSlot tone="ink" />
                <CoverSlot tone="moss" class="translate-y-6" />
            </div>
        </div>
    </section>

    <section v-if="featuredRest.length || featured.length === 0" class="mt-16 sm:mt-20">
        <div class="flex items-end justify-between gap-4 border-b border-border pb-5">
            <div class="min-w-0">
                <p class="text-[0.7rem] font-medium tracking-[0.22em] text-primary uppercase">Nossa seleção</p>
                <h2 class="mt-2 font-serif text-3xl tracking-tight text-foreground sm:text-4xl">Leituras em destaque</h2>
            </div>
            <Link :href="catalog()" class="shrink-0 text-sm font-medium text-primary underline-offset-4 hover:underline">
                Explorar catálogo
            </Link>
        </div>
        <div v-if="featuredRest.length" class="mt-8 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="book in featuredRest" :key="book.slug" class="min-w-0 transition-transform duration-300 hover:-translate-y-1">
                <BookCard :book="book" />
            </div>
        </div>
        <p v-else class="mt-6 max-w-2xl text-sm leading-6 text-foreground/80">
            Em breve, novos títulos farão parte da nossa seleção. Enquanto isso, explore o catálogo e encontre sua próxima leitura.
        </p>
    </section>

    <section v-if="recent.length" class="mt-16 rounded-[1.75rem] bg-secondary/70 px-5 py-10 sm:mt-20 sm:px-8 sm:py-12">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-[0.7rem] font-medium tracking-[0.22em] text-primary uppercase">Mais para descobrir</p>
                <h2 class="mt-2 font-serif text-3xl tracking-tight text-foreground sm:text-4xl">Outros títulos para explorar</h2>
            </div>
            <Link :href="catalog()" class="shrink-0 text-sm font-medium text-primary underline-offset-4 hover:underline">
                Ver catálogo completo
            </Link>
        </div>
        <div class="mt-8 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="book in recent" :key="book.slug" class="min-w-0 transition-transform duration-300 hover:-translate-y-1">
                <BookCard :book="book" />
            </div>
        </div>
    </section>

    <section id="como-funciona" class="mt-16 scroll-mt-32 border-t border-border pt-14 sm:mt-20">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-16">
            <div>
                <p class="text-[0.7rem] font-medium tracking-[0.22em] text-primary uppercase">Simples assim</p>
                <h2 class="mt-3 max-w-sm font-serif text-3xl leading-tight tracking-tight text-foreground sm:text-4xl">
                    Seu próximo livro em três passos.
                </h2>
            </div>
            <ol>
                <li class="border-t border-border py-6 first:border-t-0 first:pt-0">
                    <div class="grid grid-cols-[2.25rem_minmax(0,1fr)] gap-3">
                        <p class="font-serif text-lg text-primary">01</p>
                        <div>
                            <h3 class="font-medium text-foreground">Escolha sua próxima leitura</h3>
                            <p class="mt-2 text-sm leading-6 text-foreground/80">
                                Navegue pelo catálogo, descubra diferentes histórias e conheça os detalhes de cada título antes de fazer sua escolha.
                            </p>
                        </div>
                    </div>
                </li>
                <li class="border-t border-border py-6">
                    <div class="grid grid-cols-[2.25rem_minmax(0,1fr)] gap-3">
                        <p class="font-serif text-lg text-primary">02</p>
                        <div>
                            <h3 class="font-medium text-foreground">Finalize sua compra via Pix</h3>
                            <p class="mt-2 text-sm leading-6 text-foreground/80">
                                Compre seu livro de forma simples e prática, utilizando o Pix como forma de pagamento.
                            </p>
                        </div>
                    </div>
                </li>
                <li class="border-t border-border py-6">
                    <div class="grid grid-cols-[2.25rem_minmax(0,1fr)] gap-3">
                        <p class="font-serif text-lg text-primary">03</p>
                        <div>
                            <h3 class="font-medium text-foreground">Aproveite sua leitura</h3>
                            <p class="mt-2 text-sm leading-6 text-foreground/80">
                                Após a confirmação do pagamento, o livro estará disponível na sua biblioteca pessoal. É só acessar e começar a ler, diretamente pela plataforma.
                            </p>
                        </div>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <section class="mt-16 overflow-hidden rounded-[1.75rem] bg-primary text-primary-foreground sm:mt-20">
        <div class="flex flex-col gap-8 px-6 py-12 sm:px-10 sm:py-14 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-xl">
                <p class="text-[0.7rem] font-medium tracking-[0.22em] text-primary-foreground/75 uppercase">
                    Libris te liberant
                </p>
                <h2 class="mt-4 font-serif text-4xl leading-[1.08] tracking-tight text-balance sm:text-5xl">
                    Toda grande história começa com uma <span class="italic">escolha</span>.
                </h2>
                <p class="mt-4 max-w-md text-sm leading-6 text-primary-foreground/85">
                    Descubra seu próximo livro, adquira pelo Pix e comece a ler diretamente na plataforma.
                </p>
            </div>
            <Link
                :href="catalog()"
                class="inline-flex h-12 shrink-0 items-center justify-center rounded-full bg-primary-foreground px-6 text-sm font-medium text-primary transition-colors hover:bg-primary-foreground/90"
            >
                Encontrar meu próximo livro
            </Link>
        </div>
    </section>
</template>
