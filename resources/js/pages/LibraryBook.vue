<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { library } from '@/routes';

const props = defineProps<{
    book: {
        title: string;
        author: string;
        content: string;
    };
}>();

const paragraphs = computed(() => props.book.content
    .split(/\n\s*\n/)
    .map((paragraph) => paragraph.trim())
    .filter((paragraph) => paragraph !== ''));
</script>

<template>
    <Head :title="book.title" />

    <article class="mx-auto max-w-2xl py-8">
        <Link :href="library()" class="text-sm text-foreground underline-offset-4 hover:underline">
            Voltar à biblioteca
        </Link>
        <p class="mt-6 text-xs font-medium tracking-[0.16em] text-primary uppercase">Leitura</p>
        <h1 class="mt-2 font-serif text-4xl break-words text-foreground">{{ book.title }}</h1>
        <p class="mt-3 text-base text-foreground">{{ book.author }}</p>
        <div class="mt-8 space-y-4">
            <p
                v-for="(paragraph, index) in paragraphs"
                :key="index"
                class="text-base leading-7 break-words whitespace-pre-wrap text-foreground"
            >
                {{ paragraph }}
            </p>
        </div>
    </article>
</template>
