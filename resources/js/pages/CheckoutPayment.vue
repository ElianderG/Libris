<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import CheckoutController from '@/actions/App/Http/Controllers/CheckoutController';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    total: string;
    items: Array<{
        title: string;
        price: string;
    }>;
    payment: {
        status: string;
        label: string;
        pix_code: string | null;
        qr_image: string | null;
        created_at: string | null;
        can_refresh: boolean;
    } | null;
    explanation: string | null;
    canRetry: boolean;
    asksDocument: boolean;
    attempts: Array<{
        created_at: string | null;
        label: string;
        status: string;
        active: boolean;
    }>;
    orderId: number;
}>();

const copied = ref(false);

const statusClass: Record<string, string> = {
    pending: 'text-amber-700 dark:text-amber-300',
    paid: 'text-emerald-700 dark:text-emerald-300',
    failed: 'text-red-700 dark:text-red-300',
    canceled: 'text-foreground/70',
    expired: 'text-foreground/70',
};

async function copyPix(): Promise<void> {
    const code = props.payment?.pix_code;

    if (!code || !navigator.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(code);
    copied.value = true;
}
</script>

<template>
    <Head title="Pagamento" />

    <section class="mx-auto max-w-2xl py-6">
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Pedido</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Pagamento</h1>

        <div v-if="payment" class="mt-6 space-y-2 text-sm text-foreground">
            <p :class="statusClass[payment.status] ?? 'text-foreground'">
                {{ payment.label }}
            </p>
            <p>Valor {{ total }}</p>
            <p v-if="payment.created_at">{{ payment.created_at }}</p>
        </div>

        <img
            v-if="payment?.qr_image"
            :src="payment.qr_image"
            alt="QR Code do Pix"
            class="mt-4 size-48 rounded-md bg-white object-contain ring-1 ring-black/10"
        />

        <div v-if="payment?.pix_code" class="mt-4 rounded-lg border border-border bg-card px-4 py-3">
            <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Pix copia e cola</p>
            <p class="mt-2 break-all text-sm text-foreground">{{ payment.pix_code }}</p>
            <button type="button" class="mt-3 text-sm text-foreground underline-offset-4 hover:underline" @click="copyPix">
                {{ copied ? 'Copiado' : 'Copiar' }}
            </button>
        </div>

        <p v-if="explanation" class="mt-3 text-sm text-foreground">
            {{ explanation }}
        </p>

        <ul v-if="attempts.length > 0" class="mt-6 divide-y divide-border">
            <li v-for="(attempt, index) in attempts" :key="index" class="py-3 text-sm text-foreground">
                <p :class="statusClass[attempt.status] ?? 'text-foreground'">
                    {{ attempt.active ? 'Ativa' : 'Encerrada' }} · {{ attempt.label }}
                </p>
                <p v-if="attempt.created_at" class="mt-1">{{ attempt.created_at }}</p>
            </li>
        </ul>

        <ul class="mt-8 divide-y divide-border">
            <li v-for="item in items" :key="item.title" class="flex items-center justify-between gap-4 py-4">
                <p class="font-serif text-xl break-words text-foreground">{{ item.title }}</p>
                <p class="text-sm font-medium text-foreground">{{ item.price }}</p>
            </li>
        </ul>

        <Form
            v-if="payment?.can_refresh"
            v-bind="CheckoutController.refresh.form(orderId)"
            class="mt-6"
            v-slot="{ processing }"
        >
            <button
                type="submit"
                :disabled="processing"
                class="inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground disabled:opacity-60"
            >
                Atualizar status
            </button>
        </Form>

        <Form
            v-if="canRetry"
            v-bind="CheckoutController.retry.form(orderId)"
            class="mt-6 space-y-4"
            v-slot="{ errors, processing }"
        >
            <div v-if="asksDocument" class="grid gap-2">
                <Label for="document">CPF</Label>
                <Input id="document" name="document" inputmode="numeric" autocomplete="off" placeholder="000.000.000-00" />
                <InputError :message="errors.document" />
            </div>
            <button
                type="submit"
                :disabled="processing"
                class="inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground disabled:opacity-60"
            >
                Gerar novo Pix
            </button>
        </Form>
    </section>
</template>
