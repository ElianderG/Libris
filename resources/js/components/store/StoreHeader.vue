<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Menu, Search, ShoppingBag, X } from '@lucide/vue';
import { ref } from 'vue';
import ThemeToggle from '@/components/store/ThemeToggle.vue';
import { catalog, cart, home, library, login } from '@/routes';
import { home as adminHome } from '@/routes/admin';

const page = usePage();
const menuOpen = ref(false);

function closeMenu(): void {
    menuOpen.value = false;
}
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-border/80 bg-background/90 backdrop-blur-md">
        <div class="hidden border-b border-border/70 bg-primary text-primary-foreground sm:block">
            <p class="mx-auto max-w-6xl px-4 py-1.5 text-center text-xs tracking-wide">
                Livros digitais · pagamento Pix somente no ambiente de testes
            </p>
        </div>

        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
            <Link
                :href="home()"
                class="flex shrink-0 items-center gap-2 text-foreground"
                @click="closeMenu"
            >
                <span class="flex size-9 items-center justify-center rounded-md bg-primary text-primary-foreground">
                    <BookOpen class="size-4" />
                </span>
                <span class="leading-none">
                    <span class="block font-serif text-xl tracking-tight sm:text-2xl">Libris</span>
                    <span class="block font-serif text-[0.7rem] italic tracking-wide">te liberant</span>
                </span>
            </Link>

            <nav class="ml-4 hidden items-center gap-6 md:flex">
                <Link :href="catalog()" class="text-sm font-medium text-foreground hover:text-primary">
                    Catálogo
                </Link>
            </nav>

            <form
                :action="catalog.url()"
                method="get"
                role="search"
                class="relative ml-auto hidden min-w-0 max-w-sm flex-1 md:block"
            >
                <label class="sr-only" for="busca-livros">Buscar livros</label>
                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-foreground/70" />
                <input
                    id="busca-livros"
                    name="q"
                    type="search"
                    placeholder="Buscar por título ou autor"
                    class="h-10 w-full rounded-full border border-input bg-card pr-4 pl-10 text-sm text-foreground shadow-xs placeholder:text-foreground/60 focus:border-primary focus:outline-none"
                />
            </form>

            <div class="ml-auto hidden items-center gap-2 md:flex">
                <ThemeToggle />
                <Link
                    v-if="page.props.auth.user?.is_admin"
                    :href="adminHome()"
                    class="rounded-full px-3 py-2 text-sm font-medium text-foreground hover:bg-secondary"
                >
                    Painel
                </Link>
                <Link
                    v-if="page.props.auth.user"
                    :href="library()"
                    class="rounded-full px-3 py-2 text-sm font-medium text-foreground hover:bg-secondary"
                >
                    Minha conta
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="rounded-full px-3 py-2 text-sm font-medium text-foreground hover:bg-secondary"
                >
                    Entrar
                </Link>
                <Link
                    :href="cart()"
                    class="inline-flex items-center gap-2 rounded-full bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                >
                    <ShoppingBag class="size-4" />
                    Sacola
                </Link>
            </div>

            <ThemeToggle class="ml-auto md:hidden" />
            <button
                type="button"
                class="inline-flex size-10 items-center justify-center rounded-full border border-border text-foreground md:hidden"
                :aria-expanded="menuOpen"
                aria-controls="menu-da-loja"
                @click="menuOpen = !menuOpen"
            >
                <X v-if="menuOpen" class="size-4" />
                <Menu v-else class="size-4" />
                <span class="sr-only">{{ menuOpen ? 'Fechar' : 'Menu' }}</span>
            </button>
        </div>

        <div v-if="menuOpen" id="menu-da-loja" class="border-t border-border px-4 py-4 md:hidden">
            <form :action="catalog.url()" method="get" role="search" class="relative">
                <label class="sr-only" for="busca-livros-mobile">Buscar livros</label>
                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-foreground/70" />
                <input
                    id="busca-livros-mobile"
                    name="q"
                    type="search"
                    placeholder="Buscar por título ou autor"
                    class="h-11 w-full rounded-full border border-input bg-card pr-4 pl-10 text-sm text-foreground placeholder:text-foreground/60 focus:border-primary focus:outline-none"
                />
            </form>
            <nav class="mt-4 grid gap-1">
                <Link :href="catalog()" class="rounded-lg px-3 py-3 text-sm font-medium text-foreground hover:bg-secondary" @click="closeMenu">
                    Catálogo
                </Link>
                <Link
                    v-if="page.props.auth.user?.is_admin"
                    :href="adminHome()"
                    class="rounded-lg px-3 py-3 text-sm font-medium text-foreground hover:bg-secondary"
                    @click="closeMenu"
                >
                    Painel
                </Link>
                <Link
                    v-if="page.props.auth.user"
                    :href="library()"
                    class="rounded-lg px-3 py-3 text-sm font-medium text-foreground hover:bg-secondary"
                    @click="closeMenu"
                >
                    Minha conta
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="rounded-lg px-3 py-3 text-sm font-medium text-foreground hover:bg-secondary"
                    @click="closeMenu"
                >
                    Entrar
                </Link>
                <Link :href="cart()" class="rounded-lg px-3 py-3 text-sm font-medium text-foreground hover:bg-secondary" @click="closeMenu">
                    Sacola
                </Link>
            </nav>
        </div>
    </header>
</template>
