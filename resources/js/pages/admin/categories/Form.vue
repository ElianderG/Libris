<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    category: {
        id: number;
        name: string;
        books: number;
    } | null;
}>();

function confirmDelete(event: Event): void {
    if (!window.confirm('Apagar esta categoria?')) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head :title="category ? 'Editar categoria' : 'Nova categoria'" />

    <section class="mx-auto max-w-xl">
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Administração</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">
            {{ category ? 'Editar categoria' : 'Nova categoria' }}
        </h1>
        <Link :href="CategoryController.index.url()" class="mt-3 inline-flex text-sm text-foreground underline-offset-4 hover:underline">
            Voltar para as categorias
        </Link>

        <Form
            v-bind="category ? CategoryController.update.form(category.id) : CategoryController.store.form()"
            class="mt-8 space-y-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Nome</Label>
                <Input id="name" name="name" :default-value="category?.name ?? ''" required />
                <InputError :message="errors.name" />
            </div>
            <button
                type="submit"
                :disabled="processing"
                class="inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground disabled:opacity-60"
            >
                Salvar
            </button>
        </Form>

        <div v-if="category" class="mt-10 border-t border-border pt-6">
            <p v-if="category.books > 0" class="text-sm text-foreground">
                Esta categoria tem livros. Mova ou apague os livros antes de apagá-la.
            </p>
            <Form
                v-else
                v-bind="CategoryController.destroy.form(category.id)"
                v-slot="{ processing }"
            >
                <button
                    type="submit"
                    :disabled="processing"
                    class="text-sm text-red-700 underline-offset-4 hover:underline dark:text-red-300"
                    @click="confirmDelete"
                >
                    Excluir categoria
                </button>
            </Form>
        </div>
    </section>
</template>
