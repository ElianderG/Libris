<?php

namespace App\Http\Controllers;

use App\Services\Payments\ReceivePagsmileNotice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PagsmileWebhookController extends Controller
{
    public function __invoke(Request $request, ReceivePagsmileNotice $notice): Response
    {
        $header = $request->headers->get('Pagsmile-Signature');

        $status = $notice->receive(
            $request->getContent(),
            is_string($header) ? $header : '',
        );

        if ($status === 200) {
            return response('success');
        }

        return response('', $status);
    }
}
