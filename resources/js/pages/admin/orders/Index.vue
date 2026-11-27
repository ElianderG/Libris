<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';

type PageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

defineProps<{
    status: string;
    orders: {
        data: Array<{
            id: number;
            created_at: string | null;
            buyer: string;
            titles: string[];
            total: string;
            status: string;
            status_label: string;
            detail_url: string;
        }>;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        links: PageLink[];
    };
}>();

const statusClass: Record<string, string> = {
    pending_payment: 'text-amber-700 dark:text-amber-300',
    paid: 'text-emerald-700 dark:text-emerald-300',
};
</script>

<template>
    <Head title="Pedidos" />

    <section>
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Administração</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Pedidos</h1>

        <form :action="OrderController.index.url()" method="get" class="mt-8 flex flex-wrap items-center gap-3">
            <label class="grid gap-1 text-sm text-foreground">
                Status
                <select
                    name="status"
                    :value="status"
                    class="h-10 rounded-full border border-input bg-card px-4 text-sm text-foreground"
                >
                    <option value="">Todos</option>
                    <option value="pending_payment">Aguardando pagamento</option>
                    <option value="paid">Pago</option>
                </select>
            </label>
            <button type="submit" class="mt-6 h-10 rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground">
                Filtrar
            </button>
        </form>

        <p v-if="orders.data.length === 0" class="mt-8 text-base leading-7 text-foreground">
            Nenhum pedido com esse status.
        </p>
        <ul v-else class="mt-6 divide-y divide-border">
            <li v-for="order in orders.data" :key="order.id" class="py-4">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <Link :href="order.detail_url" class="font-serif text-2xl text-foreground hover:text-primary">
                        Pedido {{ order.id }}
                    </Link>
                    <p class="text-sm font-medium text-foreground">{{ order.total }}</p>
                </div>
                <p v-if="order.created_at" class="mt-1 text-sm text-foreground/70">{{ order.created_at }}</p>
                <p class="mt-1 text-sm text-foreground">{{ order.buyer }}</p>
                <p class="mt-1 text-sm text-foreground/80">{{ order.titles.join(', ') }}</p>
                <p class="mt-1 text-sm" :class="statusClass[order.status] ?? 'text-foreground'">
                    {{ order.status_label }}
                </p>
            </li>
        </ul>

        <nav v-if="orders.last_page > 1" class="mt-8 flex flex-wrap items-center gap-3 text-sm" aria-label="Páginas de pedidos">
            <Link
                v-if="orders.prev_page_url"
                :href="orders.prev_page_url"
                class="rounded-full border border-border px-4 py-2 text-foreground"
            >
                Anterior
            </Link>
            <span class="text-foreground">Página {{ orders.current_page }} de {{ orders.last_page }}</span>
            <Link
                v-if="orders.next_page_url"
                :href="orders.next_page_url"
                class="rounded-full border border-border px-4 py-2 text-foreground"
            >
                Próxima
            </Link>
        </nav>
    </section>
</template>
