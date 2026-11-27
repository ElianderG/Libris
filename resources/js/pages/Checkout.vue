<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import CartController from '@/actions/App/Http/Controllers/CartController';
import CheckoutController from '@/actions/App/Http/Controllers/CheckoutController';
import InputError from '@/components/InputError.vue';
import CoverSlot from '@/components/store/CoverSlot.vue';
import type { CatalogBook } from '@/components/store/BookCard.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const gateway = ref('juntos_se_faz');

defineProps<{
    items: CatalogBook[];
    total: string;
    payer: {
        name: string;
        email: string;
    };
}>();
</script>

<template>
    <Head title="Pagamento" />

    <section class="mx-auto max-w-2xl py-6">
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Checkout</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Pagar com Pix</h1>

        <ul class="mt-8 divide-y divide-border">
            <li v-for="item in items" :key="item.slug" class="flex gap-4 py-5">
                <img
                    v-if="item.cover_url"
                    :src="item.cover_url"
                    :alt="`Capa de ${item.title}`"
                    class="size-16 shrink-0 rounded-md object-cover ring-1 ring-black/10"
                />
                <CoverSlot v-else :label="item.title" class="w-14 shrink-0" />
                <div class="min-w-0 flex-1">
                    <h2 class="font-serif text-xl leading-snug break-words text-foreground">{{ item.title }}</h2>
                    <p class="mt-1 text-sm break-words text-foreground/80">{{ item.author }}</p>
                    <Form v-bind="CartController.destroy.form(item.slug)" v-slot="{ processing }">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="mt-2 text-sm text-foreground/80 underline-offset-4 hover:text-red-700 hover:underline disabled:opacity-60"
                        >
                            Remover
                        </button>
                    </Form>
                </div>
                <p class="text-sm font-medium text-foreground">{{ item.price }}</p>
            </li>
        </ul>
        <div class="mt-6 flex items-center justify-between gap-4">
            <p class="text-lg font-medium text-foreground">Total {{ total }}</p>
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

        <Form
            v-bind="CheckoutController.store.form()"
            class="mt-8 space-y-4"
            v-slot="{ errors, processing }"
        >
            <fieldset class="grid gap-2">
                <legend class="text-sm text-foreground">Onde gerar o Pix</legend>
                <label class="flex items-center gap-2 text-sm text-foreground">
                    <input v-model="gateway" type="radio" name="gateway" value="juntos_se_faz" class="size-4 accent-primary" />
                    Juntos Se Faz
                </label>
                <label class="flex items-center gap-2 text-sm text-foreground">
                    <input v-model="gateway" type="radio" name="gateway" value="pagsmile" class="size-4 accent-primary" />
                    Pagsmile
                </label>
            </fieldset>
            <p class="text-sm text-foreground/80">Confira o nome e o e-mail.</p>
            <div class="grid gap-2">
                <Label for="name">Nome</Label>
                <Input id="name" name="name" :default-value="payer.name" autocomplete="name" required />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="email">E-mail</Label>
                <Input id="email" name="email" type="email" :default-value="payer.email" autocomplete="username" required />
                <InputError :message="errors.email" />
            </div>
            <div v-if="gateway === 'pagsmile'" class="grid gap-2">
                <Label for="document">CPF</Label>
                <Input id="document" name="document" inputmode="numeric" autocomplete="off" placeholder="000.000.000-00" />
                <InputError :message="errors.document" />
            </div>
            <button
                type="submit"
                :disabled="processing"
                class="inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground disabled:opacity-60"
            >
                Pagar com Pix
            </button>
        </Form>
    </section>
</template>
