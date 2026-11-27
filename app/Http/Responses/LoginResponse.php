<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->session()->has('url.intended')) {
            return redirect()->intended(route('library'));
        }

        if ($request->user()?->is_admin) {
            return redirect()->route('admin.home');
        }

        return redirect()->route('library');
    }
}
