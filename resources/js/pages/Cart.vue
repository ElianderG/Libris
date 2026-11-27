<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBag } from '@lucide/vue';
import { catalog } from '@/routes';
</script>

<template>
    <Head title="Sacola" />

    <section class="mx-auto max-w-lg py-10 text-center">
        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-secondary text-primary">
            <ShoppingBag class="size-6" />
        </span>
        <h1 class="mt-5 font-serif text-4xl text-foreground">Sua sacola está vazia</h1>
        <p class="mt-3 text-base leading-7 text-foreground">
            Os livros que você separar aparecem aqui, com o preço daquele momento, antes do Pix.
        </p>
        <Link
            :href="catalog()"
            class="mt-8 inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground"
        >
            Ir ao catálogo
        </Link>
    </section>
</template>
