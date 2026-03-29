<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Middleware;

use App\Shared\Infrastructure\Messenger\Stamp\CorrelationIdStamp;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Uid\Uuid;

final readonly class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamp = $envelope->last(CorrelationIdStamp::class);
        if ($stamp === null) {
            $stamp = new CorrelationIdStamp(Uuid::v7()->toRfc4122());
            $envelope = $envelope->with($stamp);
        }

        $messageClass = get_class($envelope->getMessage());
        $pos = strrpos($messageClass, '\\');
        $shortName = $pos !== false ? substr($messageClass, $pos + 1) : $messageClass;

        $this->logger->info('Handling message', [
            'message' => $shortName,
            'class' => $messageClass,
            'correlation_id' => $stamp->correlationId,
        ]);

        $startTime = microtime(true);

        try {
            $envelope = $stack->next()->handle($envelope, $stack);

            $this->logger->info('Message handled', [
                'message' => $shortName,
                'correlation_id' => $stamp->correlationId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $envelope;
        } catch (\Throwable $e) {
            $this->logger->error('Message handling failed', [
                'message' => $shortName,
                'correlation_id' => $stamp->correlationId,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            throw $e;
        }
    }
}
