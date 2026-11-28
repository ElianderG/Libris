<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { CircleUser, KeyRound, LayoutDashboard, LogOut, Receipt, Settings, Share2 } from '@lucide/vue';
import { computed } from 'vue';
import ApiKeyController from '@/actions/App/Http/Controllers/ApiKeyController';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { getInitials } from '@/composables/useInitials';
import { affiliate, library, logout, purchases } from '@/routes';
import { home as adminHome } from '@/routes/admin';
import { edit } from '@/routes/profile';

const page = usePage();
const user = computed(() => page.props.auth.user);

function clearSession(): void {
    router.flushAll();
}
</script>

<template>
    <DropdownMenu v-if="user">
        <DropdownMenuTrigger
            class="rounded-full outline-none transition hover:opacity-80 focus-visible:ring-2 focus-visible:ring-primary"
            :aria-label="`Menu da conta de ${user.name}`"
        >
            <Avatar class="size-10 border border-border">
                <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                <AvatarFallback class="bg-primary text-sm font-medium text-primary-foreground">
                    {{ getInitials(user.name) }}
                </AvatarFallback>
            </Avatar>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuItem v-if="user.is_admin" :as-child="true">
                <Link class="w-full cursor-pointer" :href="adminHome()">
                    <LayoutDashboard />
                    Painel
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem :as-child="true">
                <Link class="w-full cursor-pointer" :href="library()">
                    <CircleUser />
                    Minha conta
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem :as-child="true">
                <Link class="w-full cursor-pointer" :href="purchases()">
                    <Receipt />
                    Compras
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem :as-child="true">
                <Link class="w-full cursor-pointer" :href="affiliate()">
                    <Share2 />
                    Indicar
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem :as-child="true">
                <Link class="w-full cursor-pointer" :href="ApiKeyController.index.url()">
                    <KeyRound />
                    Chave da API
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem :as-child="true">
                <Link class="w-full cursor-pointer" :href="edit()">
                    <Settings />
                    Configurações
                </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                :as-child="true"
                class="hover:bg-red-600/10 hover:text-red-700 focus:bg-red-600/10 focus:text-red-700 data-[highlighted]:bg-red-600/10 data-[highlighted]:text-red-700 dark:hover:bg-red-400/15 dark:hover:text-red-300 dark:focus:bg-red-400/15 dark:focus:text-red-300 dark:data-[highlighted]:bg-red-400/15 dark:data-[highlighted]:text-red-300"
            >
                <Link
                    class="w-full cursor-pointer"
                    :href="logout()"
                    as="button"
                    data-test="logout-button"
                    @click="clearSession"
                >
                    <LogOut class="text-current" />
                    Sair
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
