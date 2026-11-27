<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import ProfileAvatarController from '@/actions/App/Http/Controllers/Settings/ProfileAvatarController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type AvatarItem = {
    id: number;
    url: string;
    selected: boolean;
};

const props = defineProps<{
    avatars: AvatarItem[];
}>();

const view = 320;
const fileInput = ref<HTMLInputElement | null>(null);
const canvas = ref<HTMLCanvasElement | null>(null);
const cropOpen = ref(false);
const previewId = ref<number | null>(null);
const zoom = ref(1);
const localError = ref<string | null>(null);
const form = useForm<{ photo: File | null }>({
    photo: null,
});

const previewAvatar = computed(() => props.avatars.find((avatar) => avatar.id === previewId.value) ?? null);
const canUpload = computed(() => props.avatars.length < 8);

let image: HTMLImageElement | null = null;
let objectUrl: string | null = null;
let offsetX = 0;
let offsetY = 0;
let dragging = false;
let dragStartX = 0;
let dragStartY = 0;
let originX = 0;
let originY = 0;

function coverScale(): number {
    if (!image) {
        return 1;
    }

    return Math.max(view / image.naturalWidth, view / image.naturalHeight) * zoom.value;
}

function clampOffset(): void {
    if (!image) {
        return;
    }

    const scale = coverScale();
    const limitX = Math.max(0, (image.naturalWidth * scale - view) / 2);
    const limitY = Math.max(0, (image.naturalHeight * scale - view) / 2);
    offsetX = Math.min(limitX, Math.max(-limitX, offsetX));
    offsetY = Math.min(limitY, Math.max(-limitY, offsetY));
}

function draw(): void {
    const surface = canvas.value;

    if (!surface || !image) {
        return;
    }

    const context = surface.getContext('2d');

    if (!context) {
        return;
    }

    const scale = coverScale();
    const drawnW = image.naturalWidth * scale;
    const drawnH = image.naturalHeight * scale;
    clampOffset();
    context.clearRect(0, 0, view, view);
    context.drawImage(image, (view - drawnW) / 2 + offsetX, (view - drawnH) / 2 + offsetY, drawnW, drawnH);
}

watch(zoom, () => draw());

watch(canvas, (surface) => {
    if (surface) {
        draw();
    }
});

function openPicker(): void {
    fileInput.value?.click();
}

function onFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file) {
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        localError.value = 'Envie uma imagem JPG, PNG ou WebP.';

        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        localError.value = 'A imagem pode ter no máximo 5 MB.';

        return;
    }

    localError.value = null;

    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
    }

    objectUrl = URL.createObjectURL(file);
    const next = new Image();
    next.onload = () => {
        image = next;
        zoom.value = 1;
        offsetX = 0;
        offsetY = 0;
        cropOpen.value = true;
    };
    next.onerror = () => {
        localError.value = 'Não foi possível abrir essa imagem.';
    };
    next.src = objectUrl;
}

function closeCrop(): void {
    cropOpen.value = false;
    image = null;
    form.clearErrors();

    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
}

function onCropOpen(open: boolean): void {
    if (!open) {
        closeCrop();
    }
}

function onPointerDown(event: PointerEvent): void {
    dragging = true;
    dragStartX = event.clientX;
    dragStartY = event.clientY;
    originX = offsetX;
    originY = offsetY;
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function onPointerMove(event: PointerEvent): void {
    if (!dragging) {
        return;
    }

    offsetX = originX + (event.clientX - dragStartX);
    offsetY = originY + (event.clientY - dragStartY);
    draw();
}

function onPointerUp(): void {
    dragging = false;
}

function saveCrop(): void {
    if (!image) {
        return;
    }

    const size = 512;
    const out = document.createElement('canvas');
    out.width = size;
    out.height = size;
    const context = out.getContext('2d');

    if (!context) {
        localError.value = 'Não foi possível preparar a foto.';

        return;
    }

    const ratio = size / view;
    const scale = coverScale() * ratio;
    const drawnW = image.naturalWidth * scale;
    const drawnH = image.naturalHeight * scale;
    context.drawImage(
        image,
        (size - drawnW) / 2 + offsetX * ratio,
        (size - drawnH) / 2 + offsetY * ratio,
        drawnW,
        drawnH,
    );

    out.toBlob((blob) => {
        if (!blob) {
            localError.value = 'Não foi possível preparar a foto.';

            return;
        }

        form.photo = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
        form.post(ProfileAvatarController.store.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeCrop(),
        });
    }, 'image/jpeg', 0.9);
}

function choose(avatar: AvatarItem): void {
    router.patch(ProfileAvatarController.update.url(avatar.id), {}, { preserveScroll: true });
}

function remove(avatar: AvatarItem): void {
    router.delete(ProfileAvatarController.destroy.url(avatar.id), {
        preserveScroll: true,
        onSuccess: () => {
            previewId.value = null;
        },
    });
}

onBeforeUnmount(() => {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
    }
});
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Foto"
            description="Envie, ajuste e escolha a foto que aparece no seu perfil"
        />

        <div class="flex flex-wrap items-center gap-4">
            <img
                v-if="avatars.find((avatar) => avatar.selected)"
                :src="avatars.find((avatar) => avatar.selected)?.url"
                alt="Foto padrão do perfil"
                class="size-20 rounded-full border border-border object-cover"
            />
            <div
                v-else
                class="flex size-20 items-center justify-center rounded-full border border-dashed border-border text-xs text-muted-foreground"
            >
                Sem foto
            </div>
            <div class="grid gap-2">
                <Button type="button" :disabled="!canUpload" @click="openPicker">Enviar foto</Button>
                <p class="text-sm text-muted-foreground">Até 8 fotos. JPG, PNG ou WebP, no máximo 5 MB.</p>
            </div>
        </div>

        <InputError :message="localError ?? undefined" />

        <p v-if="avatars.length === 0" class="text-sm text-muted-foreground">
            Nenhuma foto guardada ainda.
        </p>

        <ul v-else class="grid grid-cols-3 gap-3 sm:grid-cols-4">
            <li v-for="avatar in avatars" :key="avatar.id">
                <button
                    type="button"
                    class="relative block rounded-full outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    @click="previewId = avatar.id"
                >
                    <img
                        :src="avatar.url"
                        alt="Foto guardada"
                        class="size-20 rounded-full object-cover"
                        :class="avatar.selected ? 'ring-2 ring-primary ring-offset-2 ring-offset-background' : ''"
                    />
                    <span
                        v-if="avatar.selected"
                        class="absolute -bottom-1 left-1/2 -translate-x-1/2 rounded-full bg-primary px-2 py-0.5 text-[0.65rem] font-medium text-primary-foreground"
                    >
                        Padrão
                    </span>
                </button>
            </li>
        </ul>

        <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="sr-only"
            @change="onFile"
        />

        <Dialog :open="cropOpen" @update:open="onCropOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Ajustar foto</DialogTitle>
                    <DialogDescription>
                        Arraste para enquadrar e use o zoom para aproximar. O círculo é a foto do perfil.
                    </DialogDescription>
                </DialogHeader>

                <div class="relative mx-auto h-80 w-80 overflow-hidden rounded-lg bg-muted">
                    <canvas
                        ref="canvas"
                        width="320"
                        height="320"
                        class="h-80 w-80 cursor-grab touch-none"
                        aria-label="Ajuste da foto"
                        @pointerdown="onPointerDown"
                        @pointermove="onPointerMove"
                        @pointerup="onPointerUp"
                        @pointercancel="onPointerUp"
                    />
                    <div
                        class="pointer-events-none absolute inset-0"
                        style="background: radial-gradient(circle at center, transparent 158px, rgba(0, 0, 0, 0.55) 160px)"
                    />
                </div>

                <label class="grid gap-2 text-sm text-foreground">
                    Zoom
                    <input v-model.number="zoom" type="range" min="1" max="3" step="0.01" class="w-full accent-primary" />
                </label>

                <InputError :message="form.errors.photo" />

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeCrop">Cancelar</Button>
                    <Button type="button" :disabled="form.processing" @click="saveCrop">Usar esta foto</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="previewAvatar !== null" @update:open="(open) => { if (!open) previewId = null; }">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Foto guardada</DialogTitle>
                    <DialogDescription>
                        {{ previewAvatar?.selected ? 'Esta é a foto padrão da sua conta.' : 'Você pode usar esta foto no perfil ou apagá-la.' }}
                    </DialogDescription>
                </DialogHeader>

                <img
                    v-if="previewAvatar"
                    :src="previewAvatar.url"
                    alt="Pré-visualização da foto"
                    class="mx-auto size-56 rounded-full object-cover"
                />

                <DialogFooter v-if="previewAvatar">
                    <Button
                        v-if="!previewAvatar.selected"
                        type="button"
                        variant="outline"
                        @click="choose(previewAvatar)"
                    >
                        Usar como padrão
                    </Button>
                    <Button type="button" variant="destructive" @click="remove(previewAvatar)">Excluir</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
