<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { catalog } from '@/routes';

defineProps<{
    orders: Array<{
        id: number;
        created_at: string | null;
        titles: string[];
        total: string;
        status: string;
        status_label: string;
        payment_status: string | null;
        payment_label: string | null;
        pix_url: string | null;
        detail_url: string;
    }>;
}>();

const statusClass: Record<string, string> = {
    pending_payment: 'text-amber-700 dark:text-amber-300',
    pending: 'text-amber-700 dark:text-amber-300',
    paid: 'text-emerald-700 dark:text-emerald-300',
    failed: 'text-red-700 dark:text-red-300',
    canceled: 'text-foreground/70',
    expired: 'text-foreground/70',
};
</script>

<template>
    <Head title="Compras" />

    <section class="mx-auto max-w-2xl py-6">
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Sua conta</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Compras</h1>

        <div v-if="orders.length === 0" class="mt-8">
            <p class="text-base leading-7 text-foreground">
                Suas compras aparecem aqui, com o valor e o status do Pix.
            </p>
            <Link
                :href="catalog()"
                class="mt-6 inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground"
            >
                Ver o catálogo
            </Link>
        </div>

        <ul v-else class="mt-8 divide-y divide-border">
            <li v-for="order in orders" :key="order.id" class="py-5">
                <p v-if="order.created_at" class="text-sm text-foreground/70">{{ order.created_at }}</p>
                <Link :href="order.detail_url" class="mt-1 block font-serif text-2xl break-words text-foreground hover:text-primary">
                    {{ order.titles.join(', ') }}
                </Link>
                <p class="mt-2 text-sm font-medium text-foreground">{{ order.total }}</p>
                <p class="mt-2 text-sm" :class="statusClass[order.status] ?? 'text-foreground'">
                    Pedido {{ order.status_label }}
                </p>
                <p
                    v-if="order.payment_label"
                    class="mt-1 text-sm"
                    :class="statusClass[order.payment_status ?? ''] ?? 'text-foreground'"
                >
                    Pix {{ order.payment_label }}
                </p>
                <Link
                    v-if="order.pix_url"
                    :href="order.pix_url"
                    class="mt-3 inline-flex text-sm text-foreground underline-offset-4 hover:underline"
                >
                    Continuar o Pix
                </Link>
            </li>
        </ul>
    </section>
</template>
