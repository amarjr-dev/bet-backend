<?php

namespace App\Services;

use App\Gateways\Contracts\GatewayInterface;
use App\Models\Gateway;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentGatewayService
{
    /**
     * Faz a tentativa de cobrança nos gateways ativos, seguindo a ordem de prioridade crescente.
     * Em caso de falha no gateway atual, avança para o próximo...1, 2, 3, etc....
     *
     * @param  array{amount: int, name: string, email: string, cardNumber: string, cvv: string} $payload
     * @return array{gateway: Gateway, external_id: string, status: string}
     * @throws RuntimeException se todos os gateways falharem
     */
    public function charge(array $payload): array
    {
        $gateways = Gateway::active()->get();

        if ($gateways->isEmpty()) {
            throw new RuntimeException('Não existe gateway de pagamento disponível. Cadastre um gateway para realizar as cobranças.');
        }

        $lastException = null;

        foreach ($gateways as $gateway) {
            try {
                $adapter = $this->resolveAdapter($gateway);
                $result  = $adapter->charge($payload);

                return array_merge($result, ['gateway' => $gateway]);
            } catch (RuntimeException $e) {
                $lastException = $e;
                Log::warning("Gateway {$gateway->name} falhou na cobrança. Tentando o próximo.", [
                    'gateway_id'   => $gateway->id,
                    'gateway_name' => $gateway->name,
                    'driver'       => $gateway->driver,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        throw new RuntimeException(
            'Todos os gateways falharam. Último erro: ' . ($lastException?->getMessage() ?? 'rastreado.'),
            0,
            $lastException
        );
    }

    /**
     * Reembolsa uma transação usando o mesmo gateway que a processou.
     *
     * @throws RuntimeException se o reembolso falhar
     */
    public function refund(Transaction $transaction): array
    {
        $gateway = $transaction->gateway;
        $adapter = $this->resolveAdapter($gateway);

        try {
            return $adapter->refund($transaction->external_id);
        } catch (RuntimeException $e) {
            Log::error("Falha ao reembolsar transação {$transaction->id} no gateway {$gateway->name}.", [
                'transaction_id' => $transaction->id,
                'external_id'    => $transaction->external_id,
                'gateway_name'   => $gateway->name,
                'driver'         => $gateway->driver,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function resolveAdapter(Gateway $gateway): GatewayInterface
    {
        $drivers = config('gateways.drivers', []);

        $adapterClass = $drivers[$gateway->driver] ?? null;

        if (! $adapterClass || ! class_exists($adapterClass)) {
            throw new RuntimeException("Driver de gateway inválido: {$gateway->driver}");
        }

        $credentials = is_string($gateway->credentials)
            ? json_decode($gateway->credentials, true)
            : ($gateway->credentials ?? []);

        return new $adapterClass($credentials);
    }
}
