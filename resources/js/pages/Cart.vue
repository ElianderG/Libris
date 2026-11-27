<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ShoppingBag } from '@lucide/vue';
import CartController from '@/actions/App/Http/Controllers/CartController';
import CoverSlot from '@/components/store/CoverSlot.vue';
import type { CatalogBook } from '@/components/store/BookCard.vue';
import { catalog, checkout } from '@/routes';

defineProps<{
    items: CatalogBook[];
    total: string;
}>();
</script>

<template>
    <Head title="Sacola" />

    <section v-if="items.length === 0" class="mx-auto max-w-lg py-10 text-center">
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

    <section v-else class="mx-auto max-w-2xl py-6">
        <div class="flex items-end justify-between gap-4">
            <h1 class="font-serif text-4xl text-foreground">Sacola</h1>
            <Form v-bind="CartController.clear.form()" v-slot="{ processing }">
                <button
                    type="submit"
                    :disabled="processing"
                    class="text-sm text-foreground/80 underline-offset-4 hover:text-red-700 hover:underline disabled:opacity-60"
                >
                    Esvaziar sacola
                </button>
            </Form>
        </div>
        <ul class="mt-8 divide-y divide-border">
            <li v-for="item in items" :key="item.slug" class="flex gap-4 py-5">
                <img
                    v-if="item.cover_url"
                    :src="item.cover_url"
                    :alt="`Capa de ${item.title}`"
                    class="size-20 shrink-0 rounded-md object-cover ring-1 ring-black/10"
                />
                <CoverSlot v-else :label="item.title" class="w-16 shrink-0" />
                <div class="min-w-0 flex-1">
                    <h2 class="font-serif text-xl leading-snug break-words text-foreground">{{ item.title }}</h2>
                    <p class="mt-1 text-sm break-words text-foreground/80">{{ item.author }}</p>
                    <p class="mt-2 text-sm font-medium text-foreground">{{ item.price }}</p>
                </div>
                <Form v-bind="CartController.destroy.form(item.slug)">
                    <button type="submit" class="text-sm text-foreground/80 underline-offset-4 hover:text-red-700 hover:underline">
                        Remover
                    </button>
                </Form>
            </li>
        </ul>
        <p class="mt-6 text-lg font-medium text-foreground">Total {{ total }}</p>
        <Link
            :href="checkout()"
            class="mt-6 inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground"
        >
            Seguir para o pagamento
        </Link>
    </section>
</template>
