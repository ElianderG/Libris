<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';

defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            is_admin: boolean;
            created_at: string | null;
            self: boolean;
        }>;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();
</script>

<template>
    <Head title="Usuários" />

    <section>
        <p class="text-xs font-medium tracking-[0.16em] text-primary uppercase">Administração</p>
        <h1 class="mt-2 font-serif text-4xl text-foreground">Usuários</h1>

        <p v-if="users.data.length === 0" class="mt-8 text-base leading-7 text-foreground">
            Nenhuma conta ainda.
        </p>
        <ul v-else class="mt-8 divide-y divide-border">
            <li v-for="user in users.data" :key="user.id" class="flex flex-wrap items-baseline justify-between gap-3 py-4">
                <div class="min-w-0">
                    <p class="font-serif text-2xl break-words text-foreground">{{ user.name }}</p>
                    <p class="mt-1 text-sm text-foreground/80">{{ user.email }}</p>
                    <p class="mt-1 text-sm text-foreground">
                        {{ user.is_admin ? 'Administrador' : 'Cliente' }}
                        <span v-if="user.created_at"> · desde {{ user.created_at }}</span>
                    </p>
                </div>
                <p v-if="user.self" class="text-sm text-foreground/70">Sua conta</p>
                <Form v-else v-bind="UserController.update.form(user.id)" v-slot="{ processing }">
                    <button
                        type="submit"
                        :disabled="processing"
                        class="text-sm text-foreground underline-offset-4 hover:underline disabled:opacity-60"
                    >
                        {{ user.is_admin ? 'Remover admin' : 'Promover' }}
                    </button>
                </Form>
            </li>
        </ul>

        <nav v-if="users.last_page > 1" class="mt-8 flex flex-wrap items-center gap-3 text-sm" aria-label="Páginas de usuários">
            <Link
                v-if="users.prev_page_url"
                :href="users.prev_page_url"
                class="rounded-full border border-border px-4 py-2 text-foreground"
            >
                Anterior
            </Link>
            <span class="text-foreground">Página {{ users.current_page }} de {{ users.last_page }}</span>
            <Link
                v-if="users.next_page_url"
                :href="users.next_page_url"
                class="rounded-full border border-border px-4 py-2 text-foreground"
            >
                Próxima
            </Link>
        </nav>
    </section>
</template>
