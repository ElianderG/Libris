<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CoverSlot from '@/components/store/CoverSlot.vue';
</script>

<template>
    <Head title="Catálogo" />

    <section class="py-6">
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Estante</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Catálogo</h1>
        <p class="mt-3 max-w-xl text-base leading-7 text-foreground">
            Nenhum livro publicado ainda. Quando houver títulos, eles aparecem aqui com capa, autor e preço.
        </p>

        <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <div v-for="tone in ['wine', 'ink', 'sand', 'moss']" :key="tone" class="space-y-3">
                <CoverSlot :tone="tone as 'wine' | 'ink' | 'sand' | 'moss'" />
                <div>
                    <p class="text-sm font-medium text-foreground">Aguardando cadastro</p>
                    <p class="text-sm text-foreground/80">Capa reservada</p>
                </div>
            </div>
        </div>
    </section>
</template>
