<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/Admin/BookController';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    book: {
        id: number;
        category_id: number;
        title: string;
        author: string;
        description: string;
        content: string;
        price: string;
        is_featured: boolean;
        is_published: boolean;
        cover_url: string | null;
        purchased: boolean;
    } | null;
    categories: Array<{
        id: number;
        name: string;
    }>;
}>();

const field = 'w-full rounded-md border border-input bg-card px-3 text-sm text-foreground';

function confirmDelete(event: Event): void {
    if (!window.confirm('Apagar este livro?')) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head :title="book ? 'Editar livro' : 'Novo livro'" />

    <section class="mx-auto max-w-2xl">
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Administração</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">{{ book ? 'Editar livro' : 'Novo livro' }}</h1>
        <Link :href="BookController.index.url()" class="mt-3 inline-flex text-sm text-foreground underline-offset-4 hover:underline">
            Voltar para os livros
        </Link>

        <p v-if="categories.length === 0" class="mt-8 text-base leading-7 text-foreground">
            Cadastre uma categoria antes de criar o livro.
            <Link :href="CategoryController.create.url()" class="underline underline-offset-4">Nova categoria</Link>
        </p>

        <Form
            v-else
            v-bind="book ? BookController.update.form(book.id) : BookController.store.form()"
            class="mt-8 space-y-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="category_id">Categoria</Label>
                <select id="category_id" v-once name="category_id" required :class="`${field} h-10`">
                    <option value="">Escolha</option>
                    <option
                        v-for="category in categories"
                        :key="category.id"
                        :value="category.id"
                        :selected="category.id === book?.category_id"
                    >
                        {{ category.name }}
                    </option>
                </select>
                <InputError :message="errors.category_id" />
            </div>
            <div class="grid gap-2">
                <Label for="title">Título</Label>
                <Input id="title" name="title" :default-value="book?.title ?? ''" required />
                <InputError :message="errors.title" />
            </div>
            <div class="grid gap-2">
                <Label for="author">Autor</Label>
                <Input id="author" name="author" :default-value="book?.author ?? ''" required />
                <InputError :message="errors.author" />
            </div>
            <div class="grid gap-2">
                <Label for="description">Descrição</Label>
                <textarea id="description" v-once name="description" required rows="4" :class="`${field} py-2`">{{ book?.description }}</textarea>
                <InputError :message="errors.description" />
            </div>
            <div class="grid gap-2">
                <Label for="price">Preço em reais</Label>
                <Input id="price" name="price" :default-value="book?.price ?? ''" inputmode="decimal" placeholder="37,90" required />
                <InputError :message="errors.price" />
            </div>
            <div class="grid gap-2">
                <Label for="content">Conteúdo</Label>
                <textarea id="content" v-once name="content" required rows="12" :class="`${field} py-2`">{{ book?.content }}</textarea>
                <InputError :message="errors.content" />
            </div>
            <label class="flex items-center gap-2 text-sm text-foreground">
                <input v-once type="checkbox" name="is_featured" value="1" class="size-4 accent-primary" :checked="book?.is_featured === true" />
                Destaque na home
            </label>
            <label class="flex items-center gap-2 text-sm text-foreground">
                <input v-once type="checkbox" name="is_published" value="1" class="size-4 accent-primary" :checked="book?.is_published === true" />
                Publicado na vitrine
            </label>
            <div class="grid gap-2">
                <Label for="cover">Capa</Label>
                <img
                    v-if="book?.cover_url"
                    :src="book.cover_url"
                    alt="Capa atual"
                    class="h-40 w-28 rounded-md object-cover ring-1 ring-black/10"
                />
                <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp" :class="`${field} h-10 py-1.5`" />
                <InputError :message="errors.cover" />
            </div>
            <button
                type="submit"
                :disabled="processing"
                class="inline-flex h-11 items-center rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground disabled:opacity-60"
            >
                Salvar
            </button>
        </Form>

        <div v-if="book" class="mt-10 border-t border-border pt-6">
            <Form
                v-if="book.purchased"
                v-bind="BookController.unpublish.form(book.id)"
                v-slot="{ processing }"
            >
                <p class="text-sm leading-6 text-foreground">
                    {{
                        book.is_published
                            ? 'Este livro já foi comprado. Ele não pode ser apagado. Despublique para tirá-lo da vitrine.'
                            : 'Este livro já foi comprado e está fora da vitrine.'
                    }}
                </p>
                <button
                    v-if="book.is_published"
                    type="submit"
                    :disabled="processing"
                    class="mt-3 inline-flex h-10 items-center rounded-full border border-border px-4 text-sm font-medium text-foreground disabled:opacity-60"
                >
                    Despublicar
                </button>
            </Form>
            <Form
                v-else
                v-bind="BookController.destroy.form(book.id)"
                v-slot="{ processing }"
            >
                <button
                    type="submit"
                    :disabled="processing"
                    class="text-sm text-red-700 underline-offset-4 hover:underline dark:text-red-300"
                    @click="confirmDelete"
                >
                    Excluir livro
                </button>
            </Form>
        </div>
    </section>
</template>
