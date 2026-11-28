<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApiKeyController from '@/actions/App/Http/Controllers/ApiKeyController';

defineProps<{
    activeKey: {
        prefix: string;
    } | null;
}>();

const page = usePage();
const copied = ref(false);
const plainKey = computed(() => {
    const value = page.props.flash.apiKey;

    return typeof value === 'string' ? value : '';
});

async function copyKey(): Promise<void> {
    if (plainKey.value === '' || !navigator.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(plainKey.value);
    copied.value = true;
}
</script>

<template>
    <Head title="Chave da API" />

    <section class="mx-auto max-w-2xl py-6">
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Sua conta</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Chave da API</h1>
        <p class="mt-4 max-w-xl text-base leading-7 text-foreground">
            Gere uma chave desta conta. Ela aparece inteira uma vez. Se perder ou vazar, gere outra.
        </p>

        <div v-if="plainKey" class="mt-8 rounded-lg border border-border bg-card p-4">
            <p class="text-sm font-medium text-foreground">Copie agora. Esta chave não aparece de novo.</p>
            <p class="mt-3 font-mono text-sm break-all text-foreground">{{ plainKey }}</p>
            <button
                type="button"
                class="mt-4 text-sm text-foreground underline-offset-4 hover:underline"
                @click="copyKey"
            >
                {{ copied ? 'Copiado' : 'Copiar chave' }}
            </button>
        </div>

        <div v-else-if="activeKey" class="mt-8">
            <p class="text-sm text-foreground">Chave ativa</p>
            <p class="mt-2 font-mono text-lg text-foreground">{{ activeKey.prefix }}</p>
        </div>

        <p v-else class="mt-8 text-base leading-7 text-foreground">Nenhuma chave ativa.</p>

        <div class="mt-6 flex flex-wrap items-center gap-4">
            <Form v-bind="ApiKeyController.store.form()" v-slot="{ processing }">
                <button
                    type="submit"
                    :disabled="processing"
                    class="inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground disabled:opacity-60"
                >
                    {{ activeKey ? 'Gerar outra chave' : 'Gerar chave' }}
                </button>
            </Form>
            <Form
                v-if="activeKey"
                v-bind="ApiKeyController.destroy.form()"
                v-slot="{ processing }"
            >
                <button
                    type="submit"
                    :disabled="processing"
                    class="text-sm text-foreground/80 underline-offset-4 hover:text-red-700 hover:underline disabled:opacity-60"
                >
                    Revogar
                </button>
            </Form>
        </div>
        <p v-if="activeKey" class="mt-3 text-sm text-foreground">
            Gerar outra invalida a atual na hora.
        </p>
    </section>
</template>
