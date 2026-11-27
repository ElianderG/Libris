<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import GoogleLoginLink from '@/components/GoogleLoginLink.vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Entrar',
        description: 'Acesse sua estante para continuar a leitura.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const page = usePage();
</script>

<template>
    <Head title="Entrar" />

    <section class="rounded-2xl border border-border bg-card px-5 py-6 sm:px-7 sm:py-8">
        <p
            v-if="status"
            class="mb-5 rounded-lg border border-border bg-background px-4 py-3 text-center text-sm text-foreground"
        >
            {{ status }}
        </p>

        <div class="space-y-3">
            <GoogleLoginLink />
            <PasskeyVerify :hide-separator="page.props.googleLogin" />
        </div>

        <div v-if="page.props.googleLogin" class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t border-border" />
            </div>
            <div class="relative flex justify-center text-xs tracking-[0.16em] uppercase">
                <span class="bg-card px-3 text-muted-foreground">ou</span>
            </div>
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <div class="grid gap-2">
                <Label for="email">E-mail</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="seu@email.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Senha</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Esqueceu a senha?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Senha"
                />
                <InputError :message="errors.password" />
            </div>

            <Label for="remember" class="flex items-center gap-3 text-sm">
                <Checkbox id="remember" name="remember" :tabindex="3" />
                <span>Lembrar de mim</span>
            </Label>

            <Button
                type="submit"
                class="h-11 w-full rounded-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Entrar
            </Button>

            <p class="text-center text-sm text-muted-foreground">
                Ainda não tem conta?
                <TextLink :href="register()" :tabindex="5">Criar conta</TextLink>
            </p>
        </Form>
    </section>
</template>
