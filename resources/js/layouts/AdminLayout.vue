<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Toaster } from '@/components/ui/sonner';
import FlashBanner from '@/components/store/FlashBanner.vue';
import StoreAccountMenu from '@/components/store/StoreAccountMenu.vue';
import ThemeToggle from '@/components/store/ThemeToggle.vue';
import { home as storeHome } from '@/routes';
import { affiliates, books, categories, home, orders, users } from '@/routes/admin';

defineOptions({
    inheritAttrs: false,
});

const page = usePage();
const open = ref(false);
const mobile = ref(false);
const header = ref<HTMLElement | null>(null);
const backButton = ref<HTMLButtonElement | null>(null);
const nav = ref<HTMLElement | null>(null);
const storageKey = 'admin-panel-open';

const links = [
    { label: 'Início', href: home(), exact: true },
    { label: 'Livros', href: books(), exact: false },
    { label: 'Categorias', href: categories(), exact: false },
    { label: 'Pedidos', href: orders(), exact: false },
    { label: 'Usuários', href: users(), exact: false },
    { label: 'Afiliados', href: affiliates(), exact: false },
];

let media: MediaQueryList | undefined;
let stopStart: VoidFunction | undefined;
let stopFinish: VoidFunction | undefined;
let stopCancel: VoidFunction | undefined;

function syncViewport(): void {
    mobile.value = media?.matches ?? false;
    measureHeader();

    if (!mobile.value) {
        document.body.classList.remove('admin-panel-open');
    }
}

function pathOf(url: string | URL): string {
    try {
        return new URL(url, window.location.origin).pathname.replace(/\/$/, '') || '/';
    } catch {
        return '';
    }
}

function currentPath(): string {
    return (page.url.split('?')[0] ?? '').replace(/\/$/, '') || '/';
}

function isCurrent(url: string, exact: boolean): boolean {
    const path = currentPath();
    const target = url.replace(/\/$/, '') || '/';

    return exact ? path === target : path === target || path.startsWith(`${target}/`);
}

function staysInPanel(url: string | URL): boolean {
    return pathOf(url).startsWith('/admin');
}

function measureHeader(): void {
    const height = header.value?.offsetHeight ?? 0;

    if (height > 0) {
        document.documentElement.style.setProperty('--admin-header', `${height}px`);
    }
}

function show(): void {
    window.scrollTo({ top: 0 });
    open.value = true;
}

function prepare(current: boolean): false | void {
    if (!mobile.value) {
        return;
    }

    if (current) {
        show();

        return false;
    }

    sessionStorage.setItem(storageKey, '1');
}

function revealIfRequested(): void {
    if (sessionStorage.getItem(storageKey) !== '1') {
        return;
    }

    sessionStorage.removeItem(storageKey);
    requestAnimationFrame(() => {
        requestAnimationFrame(() => show());
    });
}

function back(): void {
    open.value = false;
    nextTick(() => nav.value?.querySelector<HTMLElement>('[aria-current="page"]')?.focus());
}

watch(open, (value) => {
    document.body.classList.toggle('admin-panel-open', mobile.value && value);

    if (value) {
        nextTick(() => backButton.value?.focus());
    }
});

onMounted(() => {
    media = window.matchMedia('(max-width: 767px)');
    syncViewport();
    media.addEventListener('change', syncViewport);
    revealIfRequested();

    stopStart = router.on('start', (event) => {
        if (mobile.value && open.value && staysInPanel(event.detail.visit.url)) {
            sessionStorage.setItem(storageKey, '1');
        }
    });
    stopFinish = router.on('finish', () => revealIfRequested());
    stopCancel = router.on('cancel', () => sessionStorage.removeItem(storageKey));
});

onUnmounted(() => {
    media?.removeEventListener('change', syncViewport);
    stopStart?.();
    stopFinish?.();
    stopCancel?.();
    document.body.classList.remove('admin-panel-open');
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header ref="header" class="sticky top-0 z-40 border-b border-border bg-background">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
                <Link :href="storeHome()" class="flex shrink-0 items-center gap-2 text-foreground">
                    <span class="flex size-9 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <AppLogoIcon class="size-5" />
                    </span>
                    <span class="leading-none">
                        <span class="block font-serif text-xl tracking-tight">Libris</span>
                        <span class="block text-[0.7rem] tracking-[0.14em] text-foreground uppercase">Painel</span>
                    </span>
                </Link>
                <div class="ml-auto flex items-center gap-2">
                    <ThemeToggle />
                    <StoreAccountMenu />
                </div>
            </div>
        </header>

        <div class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 md:py-8">
            <div class="admin-layout md:grid md:grid-cols-[14rem_minmax(0,1fr)] md:items-start md:gap-8" :class="{ 'is-open': open }">
                <nav
                    ref="nav"
                    aria-label="Administração"
                    class="flex flex-col gap-1 md:sticky md:top-24"
                    :inert="mobile && open ? true : undefined"
                >
                    <p class="mb-3 text-sm text-foreground md:hidden">Escolha uma área.</p>
                    <Link
                        v-for="link in links"
                        :key="link.label"
                        :href="link.href"
                        class="rounded-lg px-3 py-3 text-base font-medium md:py-2 md:text-sm"
                        :class="isCurrent(link.href.url, link.exact)
                            ? 'bg-primary text-primary-foreground'
                            : 'text-foreground hover:bg-foreground/10'"
                        :aria-current="isCurrent(link.href.url, link.exact) ? 'page' : undefined"
                        :onBefore="() => prepare(isCurrent(link.href.url, link.exact))"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <div
                    class="admin-sheet min-w-0 transition-transform duration-300 ease-out motion-reduce:transition-none max-md:px-4 max-md:pb-10"
                    :inert="mobile && !open ? true : undefined"
                    :aria-hidden="mobile && !open ? 'true' : undefined"
                >
                    <div class="sticky top-0 z-10 -mx-4 mb-4 border-b border-border bg-background/95 px-4 py-3 backdrop-blur md:hidden">
                        <button
                            ref="backButton"
                            type="button"
                            class="inline-flex items-center gap-2 text-sm font-medium text-primary"
                            @click="back"
                        >
                            <ArrowLeft class="size-4" />
                            Voltar
                        </button>
                    </div>
                    <FlashBanner />
                    <slot />
                </div>
            </div>
        </div>
        <Toaster />
    </div>
</template>
