<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): Response
    {
        $currentId = $request->user()?->id;

        $users = User::query()
            ->select(['id', 'name', 'email', 'is_admin', 'created_at'])
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y'),
                'self' => $user->id === $currentId,
            ]);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'Você não pode alterar o próprio acesso.');
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        return back()->with('success', $user->is_admin
            ? 'Administrador promovido.'
            : 'Acesso ao painel removido.');
    }
}
