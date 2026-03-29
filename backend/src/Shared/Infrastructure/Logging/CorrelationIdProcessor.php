<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class CorrelationIdProcessor implements ProcessorInterface
{
    private ?string $correlationId = null;

    public function setCorrelationId(string $correlationId): void
    {
        $this->correlationId = $correlationId;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if ($this->correlationId !== null) {
            return $record->with(extra: array_merge($record->extra, [
                'correlation_id' => $this->correlationId,
            ]));
        }

        return $record;
    }
}
