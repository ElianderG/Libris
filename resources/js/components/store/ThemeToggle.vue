<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const page = usePage();
const { appearance, resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => {
    if (appearance.value === 'system') {
        if (page.props.appearance === 'dark') {
            return true;
        }

        if (page.props.appearance === 'light') {
            return false;
        }
    }

    return resolvedAppearance.value === 'dark';
});

function toggle(): void {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <button
        type="button"
        class="inline-flex size-10 items-center justify-center rounded-full border border-border text-foreground hover:bg-secondary"
        :aria-label="isDark ? 'Ativar modo claro' : 'Ativar modo escuro'"
        :title="isDark ? 'Modo claro' : 'Modo escuro'"
        @click="toggle"
    >
        <Sun v-if="isDark" class="size-4" />
        <Moon v-else class="size-4" />
    </button>
</template>
