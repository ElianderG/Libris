<?php

namespace App\Services\Payments;

final class GatewayPayment
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly ?string $pixCode = null,
    ) {}
}
