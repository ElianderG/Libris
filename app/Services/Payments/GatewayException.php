<?php

namespace App\Services\Payments;

use RuntimeException;

class GatewayException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Não foi possível falar com o serviço de pagamento.');
    }
}
