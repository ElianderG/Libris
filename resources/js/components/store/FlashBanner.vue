<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const page = usePage();
const visible = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

const success = computed(() => page.props.flash?.success ?? null);
const error = computed(() => page.props.flash?.error ?? null);

function hideLater(): void {
    clearTimeout(timer);

    if (!success.value && !error.value) {
        visible.value = false;

        return;
    }

    visible.value = true;
    timer = setTimeout(() => {
        visible.value = false;
    }, 5000);
}

watch([success, error], hideLater, { immediate: true });

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div v-if="visible" class="mx-auto flex w-full max-w-6xl flex-col gap-2 px-4 pt-4">
        <p
            v-if="success"
            role="status"
            class="flex items-start gap-3 rounded-xl border border-primary/25 bg-card px-4 py-3 text-sm text-foreground shadow-sm"
        >
            <CircleCheck class="mt-0.5 size-4 shrink-0 text-primary" />
            <span class="flex-1 font-medium">{{ success }}</span>
            <button type="button" class="text-foreground/70" aria-label="Fechar aviso" @click="visible = false">
                <X class="size-4" />
            </button>
        </p>
        <p
            v-if="error"
            role="alert"
            class="flex items-start gap-3 rounded-xl border border-destructive/30 bg-card px-4 py-3 text-sm text-foreground shadow-sm"
        >
            <CircleAlert class="mt-0.5 size-4 shrink-0 text-destructive" />
            <span class="flex-1 font-medium">{{ error }}</span>
            <button type="button" class="text-foreground/70" aria-label="Fechar aviso" @click="visible = false">
                <X class="size-4" />
            </button>
        </p>
    </div>
</template>
