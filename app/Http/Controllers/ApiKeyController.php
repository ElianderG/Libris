<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $key = $user->currentApiKey();

        return Inertia::render('AccountSettings', [
            'activeKey' => $key === null ? null : [
                'prefix' => $key->maskedPrefix(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $plain = ApiKey::issue($user);

        return redirect()
            ->route('account.settings')
            ->with('apiKey', $plain);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $key = $user->currentApiKey();

        abort_if($key === null, 404);

        $this->authorize('revoke', $key);

        $key->revoked_at = now();
        $key->save();

        return redirect()->route('account.settings');
    }
}
