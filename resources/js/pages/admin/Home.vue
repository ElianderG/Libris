<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    stats: {
        published_books: number;
        paid_orders: number;
        sold_total: string;
        confirmed_commissions: number;
    };
    recent: Array<{
        id: number;
        created_at: string | null;
        buyer: string;
        total: string;
        detail_url: string;
    }>;
}>();
</script>

<template>
    <Head title="Painel" />

    <section>
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Administração</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Painel</h1>
        <p class="mt-3 max-w-xl text-base leading-7 text-foreground">
            Livros na vitrine, pedidos pagos e o que a loja já vendeu.
        </p>

        <dl class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-border bg-card px-5 py-4">
                <dt class="text-sm text-foreground">Livros publicados</dt>
                <dd class="mt-2 font-serif text-3xl text-foreground">{{ stats.published_books }}</dd>
            </div>
            <div class="rounded-xl border border-border bg-card px-5 py-4">
                <dt class="text-sm text-foreground">Pedidos pagos</dt>
                <dd class="mt-2 font-serif text-3xl text-foreground">{{ stats.paid_orders }}</dd>
            </div>
            <div class="rounded-xl border border-border bg-card px-5 py-4">
                <dt class="text-sm text-foreground">Total vendido</dt>
                <dd class="mt-2 font-serif text-3xl text-foreground">{{ stats.sold_total }}</dd>
            </div>
            <div class="rounded-xl border border-border bg-card px-5 py-4">
                <dt class="text-sm text-foreground">Comissões confirmadas</dt>
                <dd class="mt-2 font-serif text-3xl text-foreground">{{ stats.confirmed_commissions }}</dd>
            </div>
        </dl>

        <h2 class="mt-10 font-serif text-2xl text-foreground">Últimos pedidos pagos</h2>
        <p v-if="recent.length === 0" class="mt-3 text-base leading-7 text-foreground">
            Nenhum pedido pago ainda.
        </p>
        <ul v-else class="mt-4 divide-y divide-border">
            <li v-for="order in recent" :key="order.id" class="flex flex-wrap items-baseline justify-between gap-3 py-4">
                <div class="min-w-0">
                    <Link :href="order.detail_url" class="font-serif text-2xl text-foreground hover:text-primary">
                        Pedido {{ order.id }}
                    </Link>
                    <p class="mt-1 text-sm text-foreground">{{ order.buyer }}</p>
                    <p v-if="order.created_at" class="mt-1 text-sm text-foreground/70">{{ order.created_at }}</p>
                </div>
                <p class="text-sm font-medium text-foreground">{{ order.total }}</p>
            </li>
        </ul>
    </section>
</template>
