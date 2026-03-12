<?php

namespace App\Gateways\Contracts;

interface GatewayInterface
{
    /**
     * Faz uma cobrança no gateway.
     *
     * @param  array{
     *     amount: int,
     *     name: string,
     *     email: string,
     *     cardNumber: string,
     *     cvv: string
     * } $payload
     * @return array{external_id: string, status: string}
     * @throws \RuntimeException caso a cobrança falhe
     */
    public function charge(array $payload): array;

    /**
     * Reembolsa uma transação no gateway.
     *
     * @param  string $externalId  ID da transação no gateway
     * @return array
     * @throws \RuntimeException em caso de falha no reembolso
     */
    public function refund(string $externalId): array;
}
