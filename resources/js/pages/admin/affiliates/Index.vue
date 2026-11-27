<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    affiliates: Array<{
        name: string;
        code: string;
        clicks: number;
        paid_orders: number;
        commission: string;
    }>;
}>();
</script>

<template>
    <Head title="Afiliados" />

    <section>
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Administração</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Afiliados</h1>
        <p class="mt-3 max-w-xl text-base leading-7 text-foreground">
            Quem já teve clique ou comissão.
        </p>

        <p v-if="affiliates.length === 0" class="mt-8 text-base leading-7 text-foreground">
            Ninguém indicou a loja ainda.
        </p>
        <div v-else class="mt-8 overflow-x-auto">
            <table class="w-full min-w-[36rem] text-left text-sm text-foreground">
                <thead>
                    <tr class="border-b border-border text-foreground/70">
                        <th class="py-3 pr-4 font-medium">Nome</th>
                        <th class="py-3 pr-4 font-medium">Código</th>
                        <th class="py-3 pr-4 font-medium">Cliques</th>
                        <th class="py-3 pr-4 font-medium">Vendas pagas</th>
                        <th class="py-3 font-medium">Comissão</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="affiliate in affiliates" :key="affiliate.code" class="border-b border-border">
                        <td class="py-3 pr-4">{{ affiliate.name }}</td>
                        <td class="py-3 pr-4 font-mono">{{ affiliate.code }}</td>
                        <td class="py-3 pr-4">{{ affiliate.clicks }}</td>
                        <td class="py-3 pr-4">{{ affiliate.paid_orders }}</td>
                        <td class="py-3">{{ affiliate.commission }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
