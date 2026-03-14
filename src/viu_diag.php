<?php
require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel para usar config() corretamente
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = new \GuzzleHttp\Client();
$apiUrl  = rtrim(config('viu.http.api_url', 'http://viu-backend:3000'), '/');
$apiKey  = config('viu.http.api_key', '');

echo "URL: {$apiUrl}\n";
echo "KEY: " . substr($apiKey, 0, 20) . "...\n\n";

$payload = [[
    'level'          => 'INFO',
    'message'        => 'Teste de integração bet-backend -> viu',
    'service'        => 'bet-backend-api',
    'environment'    => 'development',
    'timestamp'      => (new \DateTime())->format(\DateTime::RFC3339_EXTENDED),
    'source'         => 'diag',
    'correlation_id' => 'diag-' . uniqid(),
    'trace_id'       => 'diag-' . uniqid(),
    'span_id'        => substr(uniqid(), 0, 16),
    'module'         => 'diag',
    'file'           => '',
    'line'           => 0,
    'context'        => ['test' => true, 'ts' => time()],
]];

try {
    $r = $client->post($apiUrl . '/api/v1/logs', [
        'headers'     => [
            'Content-Type'  => 'application/json',
            'Authorization' => "ApiKey {$apiKey}",
        ],
        'json'        => $payload,
        'http_errors' => false,
        'timeout'     => 10,
    ]);
    echo "HTTP: " . $r->getStatusCode() . "\n";
    echo "Body: " . (string) $r->getBody() . "\n";
} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
