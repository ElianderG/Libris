<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { purchases } from '@/routes';

defineProps<{
    order: {
        id: number;
        created_at: string | null;
        status: string;
        status_label: string;
        total: string;
        items: Array<{
            title: string;
            price: string;
            read_url: string | null;
        }>;
        payments: Array<{
            created_at: string | null;
            status: string;
            label: string;
            gateway_id: string | null;
        }>;
    };
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
    <Head title="Compra" />

    <section class="mx-auto max-w-2xl py-6">
        <Link :href="purchases()" class="text-sm text-foreground underline-offset-4 hover:underline">
            Voltar às compras
        </Link>
        <p class="mt-6 text-xs font-medium tracking-[0.16em] text-primary uppercase">Comprovante</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Pedido</h1>
        <p v-if="order.created_at" class="mt-2 text-sm text-foreground/70">{{ order.created_at }}</p>
        <p class="mt-2 text-sm" :class="statusClass[order.status] ?? 'text-foreground'">
            {{ order.status_label }}
        </p>

        <ul class="mt-8 divide-y divide-border">
            <li v-for="item in order.items" :key="item.title" class="flex items-start justify-between gap-4 py-4">
                <div>
                    <p class="font-serif text-2xl break-words text-foreground">{{ item.title }}</p>
                    <Link
                        v-if="item.read_url"
                        :href="item.read_url"
                        class="mt-2 inline-flex text-sm text-foreground underline-offset-4 hover:underline"
                    >
                        Ler
                    </Link>
                </div>
                <p class="text-sm font-medium text-foreground">{{ item.price }}</p>
            </li>
        </ul>
        <p class="mt-6 text-lg font-medium text-foreground">Total {{ order.total }}</p>

        <h2 class="mt-10 font-serif text-2xl text-foreground">Tentativas de Pix</h2>
        <p v-if="order.payments.length === 0" class="mt-3 text-sm text-foreground">
            Nenhuma cobrança foi registrada.
        </p>
        <ul v-else class="mt-4 divide-y divide-border">
            <li v-for="(payment, index) in order.payments" :key="index" class="py-4 text-sm text-foreground">
                <p :class="statusClass[payment.status] ?? 'text-foreground'">{{ payment.label }}</p>
                <p v-if="payment.created_at" class="mt-1">{{ payment.created_at }}</p>
                <p v-if="payment.gateway_id" class="mt-1">Cobrança {{ payment.gateway_id }}</p>
            </li>
        </ul>
    </section>
</template>
