<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const code = computed(() => {
    const value = page.props.auth.user?.affiliate_code;

    return typeof value === 'string' ? value : '';
});
const copied = ref(false);

async function copyCode(): Promise<void> {
    if (code.value === '' || !navigator.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(code.value);
    copied.value = true;
}
</script>

<template>
    <Head title="Indicar" />

    <section class="mx-auto max-w-2xl py-6">
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Sua conta</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Indicar</h1>
        <p class="mt-4 max-w-xl text-base leading-7 text-foreground">
            A comissão incide sobre compras de outras pessoas feitas por esse link.
        </p>
        <p class="mt-6 font-serif text-3xl tracking-widest text-foreground">{{ code }}</p>
        <button
            type="button"
            class="mt-4 text-sm text-foreground underline-offset-4 hover:underline"
            @click="copyCode"
        >
            {{ copied ? 'Copiado' : 'Copiar código' }}
        </button>
    </section>
</template>
