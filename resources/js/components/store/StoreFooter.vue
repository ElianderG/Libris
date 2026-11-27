<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { catalog, cart, home, login } from '@/routes';
</script>

<template>
    <footer class="mt-auto border-t border-border bg-card">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-3">
            <div>
                <p class="font-serif text-2xl text-foreground">Libris <span class="italic">te liberant</span></p>
                <p class="mt-2 max-w-xs text-sm leading-6 text-foreground">
                    Livros digitais para ler na própria plataforma, com pagamento Pix em ambiente de testes.
                </p>
            </div>
            <div>
                <p class="text-xs font-medium tracking-[0.16em] text-foreground uppercase">Loja</p>
                <nav class="mt-3 grid gap-2 text-sm">
                    <Link :href="home()" class="text-foreground hover:text-primary">Início</Link>
                    <Link :href="catalog()" class="text-foreground hover:text-primary">Catálogo</Link>
                    <Link :href="cart()" class="text-foreground hover:text-primary">Sacola</Link>
                </nav>
            </div>
            <div>
                <p class="text-xs font-medium tracking-[0.16em] text-foreground uppercase">Conta</p>
                <nav class="mt-3 grid gap-2 text-sm">
                    <Link :href="login()" class="text-foreground hover:text-primary">Entrar</Link>
                </nav>
            </div>
        </div>
        <div class="border-t border-border">
            <p class="mx-auto max-w-6xl px-4 py-4 text-xs text-foreground">
                Libris te liberant · sem movimentação de dinheiro real
            </p>
        </div>
    </footer>
</template>
