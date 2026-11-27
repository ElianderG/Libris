<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Ellipsis, Receipt, Shield, ShieldOff, Trash2 } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type Account = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string | null;
    orders_count: number;
    self: boolean;
    can_delete: boolean;
};

defineProps<{
    users: {
        data: Account[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

const pending = ref<{ account: Account; action: 'admin' | 'delete' } | null>(null);

const dialogTitle = computed(() => {
    if (pending.value === null) {
        return '';
    }

    if (pending.value.action === 'delete') {
        return pending.value.account.can_delete ? 'Apagar a conta?' : 'Essa conta não pode ser apagada';
    }

    return pending.value.account.is_admin ? 'Remover o acesso ao painel?' : 'Tornar administrador?';
});

const dialogText = computed(() => {
    if (pending.value === null) {
        return '';
    }

    const name = pending.value.account.name;

    if (pending.value.action === 'delete') {
        return pending.value.account.can_delete
            ? `${name} sai da loja. Essa ação não volta.`
            : `${name} tem pedidos ou comissões. O histórico da loja fica.`;
    }

    return pending.value.account.is_admin
        ? `${name} deixa de entrar no painel e continua como cliente.`
        : `${name} passa a entrar no painel.`;
});

function ordersLabel(count: number): string {
    if (count === 0) {
        return 'Nenhum pedido';
    }

    return count === 1 ? '1 pedido' : `${count} pedidos`;
}

function ask(account: Account, action: 'admin' | 'delete'): void {
    nextTick(() => {
        pending.value = { account, action };
    });
}

function close(): void {
    pending.value = null;
}
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
            <li v-for="user in users.data" :key="user.id" class="flex items-start justify-between gap-3 py-4">
                <div class="min-w-0">
                    <p class="font-serif text-2xl break-words text-foreground">{{ user.name }}</p>
                    <p class="mt-1 text-sm text-foreground/80">{{ user.email }}</p>
                    <p class="mt-1 text-sm text-foreground">
                        {{ user.is_admin ? 'Administrador' : 'Cliente' }}
                        <span> · {{ ordersLabel(user.orders_count) }}</span>
                        <span v-if="user.created_at"> · desde {{ user.created_at }}</span>
                    </p>
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex size-9 shrink-0 items-center justify-center rounded-full border border-border text-foreground hover:bg-foreground/5"
                            :aria-label="`Ações de ${user.name}`"
                        >
                            <Ellipsis class="size-4" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <DropdownMenuLabel v-if="user.self">Sua conta</DropdownMenuLabel>
                        <DropdownMenuSeparator v-if="user.self" />
                        <DropdownMenuItem as-child>
                            <Link class="w-full cursor-pointer" :href="OrderController.index.url({ query: { user: user.id } })">
                                <Receipt />
                                Ver pedidos
                            </Link>
                        </DropdownMenuItem>
                        <template v-if="!user.self">
                            <DropdownMenuItem class="cursor-pointer" @select="ask(user, 'admin')">
                                <Shield v-if="!user.is_admin" />
                                <ShieldOff v-else />
                                {{ user.is_admin ? 'Remover administrador' : 'Tornar administrador' }}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem variant="destructive" class="cursor-pointer" @select="ask(user, 'delete')">
                                <Trash2 />
                                Apagar conta
                            </DropdownMenuItem>
                        </template>
                    </DropdownMenuContent>
                </DropdownMenu>
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

    <Dialog :open="pending !== null" @update:open="(open) => { if (!open) close() }">
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ dialogTitle }}</DialogTitle>
                <DialogDescription>{{ dialogText }}</DialogDescription>
            </DialogHeader>
            <DialogFooter v-if="pending?.action === 'delete' && pending.account.can_delete === false" class="gap-2">
                <Button type="button" variant="secondary" @click="close">Fechar</Button>
            </DialogFooter>
            <Form
                v-else-if="pending?.action === 'admin'"
                v-bind="UserController.update.form(pending.account.id)"
                @success="close"
                v-slot="{ processing }"
            >
                <DialogFooter class="gap-2">
                    <Button type="button" variant="secondary" @click="close">Cancelar</Button>
                    <Button type="submit" :disabled="processing">
                        {{ pending.account.is_admin ? 'Remover acesso' : 'Tornar administrador' }}
                    </Button>
                </DialogFooter>
            </Form>
            <Form
                v-else-if="pending?.action === 'delete' && pending.account.can_delete"
                v-bind="UserController.destroy.form(pending.account.id)"
                @success="close"
                v-slot="{ processing }"
            >
                <DialogFooter class="gap-2">
                    <Button type="button" variant="secondary" @click="close">Cancelar</Button>
                    <Button type="submit" variant="destructive" :disabled="processing">Apagar conta</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
