<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { buttonVariants } from '@/components/ui/button';

const page = usePage();
</script>

<template>
    <a
        v-if="page.props.googleLogin"
        href="/auth/google"
        :class="buttonVariants({ variant: 'outline' })"
        class="w-full"
    >
        Continuar com Google
    </a>
</template>
