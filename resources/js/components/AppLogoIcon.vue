<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        fill="none"
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
    >
        <path
            fill="currentColor"
            d="M15.2 8.2c-2.4-1.4-5.1-2-8-2H4.5v16.4h2.7c2.6 0 5 .5 8 2.1V8.2Z"
        />
        <path
            fill="currentColor"
            d="M16.8 8.2c2.4-1.4 5.1-2 8-2h2.7v16.4h-2.7c-2.6 0-5 .5-8 2.1V8.2Z"
        />
        <path
            fill="currentColor"
            d="M15.1 9.2h1.8v15.2h-1.8z"
        />
    </svg>
</template>
