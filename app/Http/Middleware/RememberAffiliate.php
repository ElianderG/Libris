<?php

namespace App\Http\Middleware;

use App\Models\AffiliateClick;
use App\Models\Product;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class RememberAffiliate
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ref = $request->query('ref');

        if (! is_string($ref) || $ref === '') {
            return $next($request);
        }

        $affiliate = User::query()->where('affiliate_code', $ref)->first();

        if ($affiliate === null || $affiliate->id === $request->user()?->id) {
            return $next($request);
        }

        $token = $request->cookie('libris_ref');
        $sameClick = is_string($token) && $token !== ''
            ? AffiliateClick::query()->where('token', $token)->where('affiliate_user_id', $affiliate->id)->exists()
            : false;

        if (! $sameClick) {
            $click = new AffiliateClick;
            $click->affiliate_user_id = $affiliate->id;
            $click->product_id = $this->productId($request);
            $click->token = Str::random(40);
            $agent = $request->userAgent();
            $click->user_agent = is_string($agent) && $agent !== '' ? mb_substr($agent, 0, 255) : null;
            $click->save();
            $token = $click->token;
        }

        return $this->withoutRef($request)->cookie($this->cookie((string) $token));
    }

    private function productId(Request $request): ?int
    {
        if ($request->route()?->getName() !== 'books.show') {
            return null;
        }

        $slug = $request->route('slug');

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        $id = Product::query()->where('slug', $slug)->value('id');

        return is_numeric($id) ? (int) $id : null;
    }

    private function withoutRef(Request $request): RedirectResponse
    {
        $query = $request->query();
        unset($query['ref']);

        $target = $request->url();

        if ($query !== []) {
            $target .= '?'.http_build_query($query);
        }

        return redirect()->to($target);
    }

    private function cookie(string $token): Cookie
    {
        $minutes = max(1, (int) config('libris.affiliate.cookie_days')) * 24 * 60;

        return cookie('libris_ref', $token, $minutes, '/', null, null, true, false, 'lax');
    }
}
