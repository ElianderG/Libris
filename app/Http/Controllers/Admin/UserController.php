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
            ->withCount(['orders', 'commissions', 'referredOrders'])
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y'),
                'orders_count' => $user->orders_count,
                'self' => $user->id === $currentId,
                'can_delete' => $user->orders_count === 0
                    && $user->commissions_count === 0
                    && $user->referred_orders_count === 0,
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

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'Você não pode apagar a própria conta.');
        }

        if ($this->hasStoreHistory($user)) {
            return back()->with('error', 'Essa conta tem pedidos ou comissões e não pode ser apagada.');
        }

        $user->delete();

        return back()->with('success', 'Conta apagada.');
    }

    private function hasStoreHistory(User $user): bool
    {
        return $user->orders()->exists()
            || $user->commissions()->exists()
            || $user->referredOrders()->exists();
    }
}
