<?php

namespace App\Logging;

use Monolog\Logger;
use Viu\ViuLaravel\ViuLogger;

/**
 * Factory para o canal 'viu' em config/logging.php.
 *
 * Reutiliza o ViuMonologHandler do singleton (app(ViuLogger::class)) para garantir
 * que os IDs injetados pelo ViuCorrelationMiddleware (correlation_id, trace_id, span_id)
 * estejam presentes em todos os logs enviados via Log::info() / Log::channel('viu').
 */
class CreateViuLogger
{
    public function __invoke(array $config): Logger
    {
        /** @var ViuLogger $viuLogger */
        $viuLogger = app(ViuLogger::class);

        return new Logger('viu', [$viuLogger->getHandler()]);
    }
}
