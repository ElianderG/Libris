<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { orders } from '@/routes/admin';

defineProps<{
    order: {
        id: number;
        created_at: string | null;
        buyer: string;
        email: string;
        status: string;
        status_label: string;
        total: string;
        items: Array<{
            title: string;
            quantity: number;
            price: string;
        }>;
        payments: Array<{
            id: number;
            created_at: string | null;
            status: string;
            label: string;
            amount: string;
            gateway_id: string | null;
            raw: string | null;
            settle_url: string | null;
            actions: Array<{
                name: string;
                label: string;
            }>;
        }>;
    };
}>();

const confirms: Record<string, string> = {
    approve: 'Aprovar este Pix e marcar o pedido como pago?',
    decline: 'Recusar este Pix?',
    cancel: 'Cancelar este Pix?',
    expire: 'Marcar este Pix como expirado?',
};

const pendingAction = ref<string | null>(null);

function settle(url: string, paymentId: number, action: string): void {
    if (pendingAction.value !== null) {
        return;
    }

    if (!window.confirm(confirms[action] ?? 'Confirmar esta ação?')) {
        return;
    }

    pendingAction.value = `${paymentId}:${action}`;

    router.post(
        url,
        { action },
        {
            preserveScroll: true,
            onFinish: () => {
                pendingAction.value = null;
            },
        },
    );
}

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
    <Head :title="`Pedido ${order.id}`" />

    <section class="mx-auto max-w-2xl">
        <Link :href="orders()" class="text-sm text-foreground underline-offset-4 hover:underline">
            Voltar para os pedidos
        </Link>
        <p class="mt-6 text-xs font-medium tracking-[0.16em] text-primary uppercase">Administração</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Pedido {{ order.id }}</h1>
        <p v-if="order.created_at" class="mt-2 text-sm text-foreground/70">{{ order.created_at }}</p>
        <p class="mt-2 text-sm text-foreground">{{ order.buyer }} · {{ order.email }}</p>
        <p class="mt-2 text-sm" :class="statusClass[order.status] ?? 'text-foreground'">
            {{ order.status_label }}
        </p>

        <ul class="mt-8 divide-y divide-border">
            <li v-for="item in order.items" :key="item.title" class="flex items-start justify-between gap-4 py-4">
                <p class="font-serif text-2xl break-words text-foreground">
                    {{ item.quantity }} × {{ item.title }}
                </p>
                <p class="text-sm font-medium text-foreground">{{ item.price }}</p>
            </li>
        </ul>
        <p class="mt-6 text-lg font-medium text-foreground">Total {{ order.total }}</p>

        <h2 class="mt-10 font-serif text-2xl text-foreground">Tentativas de Pix</h2>
        <p class="mt-2 text-sm leading-6 text-foreground/80">
            O sandbox não guarda o status. Na tentativa pendente, a ação pede a simulação ao gateway e aplica a resposta neste pedido.
        </p>
        <p v-if="order.payments.length === 0" class="mt-3 text-sm text-foreground">
            Nenhuma cobrança foi registrada.
        </p>
        <ul v-else class="mt-4 divide-y divide-border">
            <li v-for="payment in order.payments" :key="payment.id" class="py-4 text-sm text-foreground">
                <p :class="statusClass[payment.status] ?? 'text-foreground'">{{ payment.label }}</p>
                <p v-if="payment.created_at" class="mt-1">{{ payment.created_at }}</p>
                <p class="mt-1">{{ payment.amount }}</p>
                <p v-if="payment.gateway_id" class="mt-1">Cobrança {{ payment.gateway_id }}</p>
                <div v-if="payment.settle_url && payment.actions.length > 0" class="mt-3 flex flex-wrap gap-2">
                    <button
                        v-for="action in payment.actions"
                        :key="action.name"
                        type="button"
                        class="inline-flex h-10 items-center rounded-full px-4 text-sm font-medium disabled:opacity-60"
                        :class="
                            action.name === 'approve'
                                ? 'bg-primary text-primary-foreground'
                                : 'border border-border text-foreground'
                        "
                        :disabled="pendingAction !== null"
                        @click="payment.settle_url && settle(payment.settle_url, payment.id, action.name)"
                    >
                        {{ action.label }}
                    </button>
                </div>
                <details v-if="payment.raw" class="mt-3">
                    <summary class="cursor-pointer text-foreground/80">Dados do gateway</summary>
                    <pre class="mt-2 overflow-x-auto rounded-md border border-border bg-card p-3 text-xs leading-5">{{ payment.raw }}</pre>
                </details>
            </li>
        </ul>
    </section>
</template>
