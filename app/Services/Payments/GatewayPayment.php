<?php

namespace App\Services\Payments;

final class GatewayPayment
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly ?string $pixCode = null,
        public readonly ?int $amountCents = null,
        public readonly ?string $qrCodeUrl = null,
        public readonly ?string $qrCodeImg = null,
    ) {}
}
