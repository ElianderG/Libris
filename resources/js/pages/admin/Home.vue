<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, Receipt, Share2, Tags, Users } from '@lucide/vue';
import AdminChart from '@/components/admin/AdminChart.vue';
import { affiliates, books, categories, orders, users } from '@/routes/admin';

defineProps<{
    stats: {
        published_books: number;
        paid_orders: number;
        sold_total: string;
        confirmed_commissions: number;
    };
    charts: {
        sales: { labels: string[]; values: number[] };
        orders: { labels: string[]; values: number[] };
        books: { labels: string[]; values: number[] };
    };
    recent: Array<{
        id: number;
        created_at: string | null;
        buyer: string;
        total: string;
        detail_url: string;
    }>;
}>();

const sections = [
    { label: 'Livros', text: 'Cadastrar e publicar', href: books(), icon: BookOpen },
    { label: 'Categorias', text: 'Organizar a vitrine', href: categories(), icon: Tags },
    { label: 'Pedidos', text: 'Pagamentos e Pix', href: orders(), icon: Receipt },
    { label: 'Usuários', text: 'Acessos de administrador', href: users(), icon: Users },
    { label: 'Afiliados', text: 'Cliques e comissões', href: affiliates(), icon: Share2 },
];
</script>

<template>
    <Head title="Painel" />

    <section>
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Administração</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Painel</h1>
        <p class="mt-3 max-w-xl text-base leading-7 text-foreground">
            Atalhos das áreas e o movimento da loja.
        </p>

        <nav class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="Áreas do painel">
            <Link
                v-for="section in sections"
                :key="section.label"
                :href="section.href"
                class="flex items-center gap-3 rounded-xl border border-border bg-card px-4 py-4 text-foreground transition-colors hover:border-primary"
            >
                <component :is="section.icon" class="size-5 shrink-0 text-primary" />
                <span class="min-w-0">
                    <span class="block font-medium">{{ section.label }}</span>
                    <span class="block text-sm text-foreground/70">{{ section.text }}</span>
                </span>
            </Link>
        </nav>

        <dl class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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

        <div class="mt-8 grid gap-4 lg:grid-cols-2">
            <article class="rounded-xl border border-border bg-card px-5 py-4 lg:col-span-2">
                <h2 class="font-serif text-2xl text-foreground">Vendas pagas</h2>
                <p class="mt-1 text-sm text-foreground/70">Últimos 14 dias</p>
                <div class="mt-4">
                    <AdminChart type="bar" label="Vendas pagas dos últimos 14 dias" :labels="charts.sales.labels" :values="charts.sales.values" money />
                </div>
            </article>
            <article class="rounded-xl border border-border bg-card px-5 py-4">
                <h2 class="font-serif text-2xl text-foreground">Pedidos</h2>
                <p v-if="charts.orders.values.every((value) => value === 0)" class="mt-4 text-sm text-foreground">
                    Nenhum pedido ainda.
                </p>
                <div v-else class="mt-4">
                    <AdminChart type="doughnut" label="Pedidos por status" :labels="charts.orders.labels" :values="charts.orders.values" />
                </div>
            </article>
            <article class="rounded-xl border border-border bg-card px-5 py-4">
                <h2 class="font-serif text-2xl text-foreground">Livros publicados</h2>
                <p v-if="charts.books.labels.length === 0" class="mt-4 text-sm text-foreground">
                    Nenhum livro publicado ainda.
                </p>
                <div v-else class="mt-4">
                    <AdminChart type="bar" label="Livros publicados por categoria" :labels="charts.books.labels" :values="charts.books.values" />
                </div>
            </article>
        </div>

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
