<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CoverSlot from '@/components/store/CoverSlot.vue';
import { catalog } from '@/routes';
</script>

<template>
    <Head title="Início" />

    <section class="grid items-center gap-10 py-6 lg:grid-cols-[1.15fr_0.85fr] lg:py-10">
        <div>
            <p class="text-xs font-medium tracking-[0.18em] text-primary uppercase">
                Livraria digital
            </p>
            <h1 class="mt-3 max-w-xl font-serif text-4xl leading-tight text-foreground sm:text-5xl">
                Uma estante só sua, para ler sem baixar nada.
            </h1>
            <p class="mt-4 max-w-lg text-base leading-7 text-foreground">
                Escolha um livro, pague com Pix no ambiente de testes e volte aqui para continuar a leitura.
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
                    Como a estante funciona
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
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-medium tracking-[0.16em] text-foreground uppercase">Em breve</p>
                <h2 class="mt-2 font-serif text-3xl text-foreground">A estante ainda não tem títulos</h2>
            </div>
        </div>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-foreground">
            Os espaços abaixo são o lugar das capas. Eles passam a ser livros de verdade quando o catálogo for cadastrado.
        </p>
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <CoverSlot tone="sand" />
            <CoverSlot tone="wine" />
            <CoverSlot tone="ink" />
            <CoverSlot tone="moss" />
        </div>
    </section>

    <section id="como-funciona" class="mt-14 grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-border bg-card p-5">
            <p class="font-serif text-2xl text-primary">01</p>
            <h3 class="mt-3 font-medium text-foreground">Escolha o livro</h3>
            <p class="mt-2 text-sm leading-6 text-foreground">O catálogo mostra capa, autor e preço antes de qualquer pagamento.</p>
        </article>
        <article class="rounded-2xl border border-border bg-card p-5">
            <p class="font-serif text-2xl text-primary">02</p>
            <h3 class="mt-3 font-medium text-foreground">Pague com Pix</h3>
            <p class="mt-2 text-sm leading-6 text-foreground">A cobrança acontece no sandbox. Nenhum valor real é movimentado.</p>
        </article>
        <article class="rounded-2xl border border-border bg-card p-5">
            <p class="font-serif text-2xl text-primary">03</p>
            <h3 class="mt-3 font-medium text-foreground">Leia na estante</h3>
            <p class="mt-2 text-sm leading-6 text-foreground">Depois da confirmação, o livro fica na sua biblioteca, dentro da plataforma.</p>
        </article>
    </section>
</template>
