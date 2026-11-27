<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreProfileAvatarRequest;
use App\Models\UserAvatar;
use App\Support\AvatarImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use RuntimeException;

class ProfileAvatarController extends Controller
{
    public function show(UserAvatar $avatar): Response
    {
        $prefix = 'avatars/'.(int) $avatar->user_id.'/';

        if (! str_starts_with($avatar->path, $prefix) || str_contains($avatar->path, '..')) {
            abort(404);
        }

        $jpeg = Storage::disk('public')->get($avatar->path);

        if (! is_string($jpeg) || ! str_starts_with($jpeg, "\xFF\xD8")) {
            abort(404);
        }

        return response($jpeg, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function store(StoreProfileAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if ($user->avatars()->count() >= UserAvatar::MAX_PER_USER) {
            throw ValidationException::withMessages([
                'photo' => 'Você pode guardar até '.UserAvatar::MAX_PER_USER.' fotos. Apague uma para enviar outra.',
            ]);
        }

        $photo = $request->file('photo');

        if ($photo === null) {
            throw ValidationException::withMessages([
                'photo' => 'Escolha uma imagem.',
            ]);
        }

        try {
            $avatar = AvatarImage::store($photo, $user);
        } catch (RuntimeException) {
            throw ValidationException::withMessages([
                'photo' => 'Não foi possível processar essa imagem.',
            ]);
        }

        if ($user->avatar_id === null) {
            $user->forceFill(['avatar_id' => $avatar->id])->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Foto adicionada.']);

        return to_route('profile.edit');
    }

    public function update(Request $request, UserAvatar $avatar): RedirectResponse
    {
        $user = $request->user();

        if ($user === null || $avatar->user_id !== $user->id) {
            abort(404);
        }

        $user->forceFill(['avatar_id' => $avatar->id])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Foto padrão atualizada.']);

        return to_route('profile.edit');
    }

    public function destroy(Request $request, UserAvatar $avatar): RedirectResponse
    {
        $user = $request->user();

        if ($user === null || $avatar->user_id !== $user->id) {
            abort(404);
        }

        if ($user->avatar_id === $avatar->id) {
            $user->forceFill(['avatar_id' => null])->save();
        }

        $avatar->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Foto removida.']);

        return to_route('profile.edit');
    }
}
