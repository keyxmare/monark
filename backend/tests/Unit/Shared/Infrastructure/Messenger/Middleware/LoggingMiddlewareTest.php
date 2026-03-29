<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Logging\CorrelationIdProcessor;
use App\Shared\Infrastructure\Messenger\Middleware\LoggingMiddleware;
use App\Shared\Infrastructure\Messenger\Stamp\CorrelationIdStamp;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

function stubLogger(): LoggerInterface
{
    return new class () implements LoggerInterface {
        /** @var array<int, array{level: string, message: string, context: array<string, mixed>}> */
        public array $logs = [];

        public function emergency(\Stringable|string $message, array $context = []): void
        {
            $this->log('emergency', $message, $context);
        }

        public function alert(\Stringable|string $message, array $context = []): void
        {
            $this->log('alert', $message, $context);
        }

        public function critical(\Stringable|string $message, array $context = []): void
        {
            $this->log('critical', $message, $context);
        }

        public function error(\Stringable|string $message, array $context = []): void
        {
            $this->log('error', $message, $context);
        }

        public function warning(\Stringable|string $message, array $context = []): void
        {
            $this->log('warning', $message, $context);
        }

        public function notice(\Stringable|string $message, array $context = []): void
        {
            $this->log('notice', $message, $context);
        }

        public function info(\Stringable|string $message, array $context = []): void
        {
            $this->log('info', $message, $context);
        }

        public function debug(\Stringable|string $message, array $context = []): void
        {
            $this->log('debug', $message, $context);
        }

        public function log($level, \Stringable|string $message, array $context = []): void
        {
            $this->logs[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
        }
    };
}

function stubStack(?Envelope $returnEnvelope = null): StackInterface
{
    return new class ($returnEnvelope) implements StackInterface {
        public function __construct(private ?Envelope $returnEnvelope)
        {
        }

        public function next(): MiddlewareInterface
        {
            $envelope = $this->returnEnvelope;

            return new class ($envelope) implements MiddlewareInterface {
                public function __construct(private ?Envelope $envelope)
                {
                }

                public function handle(Envelope $envelope, StackInterface $stack): Envelope
                {
                    return $this->envelope ?? $envelope;
                }
            };
        }
    };
}

function throwingStack(\Throwable $exception): StackInterface
{
    return new class ($exception) implements StackInterface {
        public function __construct(private \Throwable $exception)
        {
        }

        public function next(): MiddlewareInterface
        {
            $exception = $this->exception;

            return new class ($exception) implements MiddlewareInterface {
                public function __construct(private \Throwable $exception)
                {
                }

                public function handle(Envelope $envelope, StackInterface $stack): Envelope
                {
                    throw $this->exception;
                }
            };
        }
    };
}

describe('LoggingMiddleware', function () {
    it('adds a CorrelationIdStamp when none exists', function () {
        $logger = \stubLogger();
        $middleware = new LoggingMiddleware($logger, new CorrelationIdProcessor());
        $envelope = new Envelope(new \stdClass());

        $result = $middleware->handle($envelope, \stubStack());

        $stamp = $result->last(CorrelationIdStamp::class);
        expect($stamp)->not->toBeNull();
        expect($stamp->correlationId)->toBeString()->not->toBeEmpty();
    });

    it('reuses existing CorrelationIdStamp', function () {
        $logger = \stubLogger();
        $middleware = new LoggingMiddleware($logger, new CorrelationIdProcessor());
        $existingId = 'existing-correlation-id';
        $envelope = (new Envelope(new \stdClass()))->with(new CorrelationIdStamp($existingId));

        $result = $middleware->handle($envelope, \stubStack());

        $stamp = $result->last(CorrelationIdStamp::class);
        expect($stamp->correlationId)->toBe($existingId);
    });

    it('logs handling and handled info messages on success', function () {
        $logger = \stubLogger();
        $middleware = new LoggingMiddleware($logger, new CorrelationIdProcessor());
        $envelope = new Envelope(new \stdClass());

        $middleware->handle($envelope, \stubStack());

        expect($logger->logs)->toHaveCount(2);
        expect($logger->logs[0]['level'])->toBe('info');
        expect($logger->logs[0]['message'])->toBe('Handling message');
        expect($logger->logs[0]['context'])->toHaveKeys(['message', 'class', 'correlation_id']);
        expect($logger->logs[1]['level'])->toBe('info');
        expect($logger->logs[1]['message'])->toBe('Message handled');
        expect($logger->logs[1]['context'])->toHaveKeys(['message', 'correlation_id', 'duration_ms']);
    });

    it('logs error message on failure and rethrows exception', function () {
        $logger = \stubLogger();
        $middleware = new LoggingMiddleware($logger, new CorrelationIdProcessor());
        $envelope = new Envelope(new \stdClass());
        $exception = new \RuntimeException('Something went wrong');

        expect(fn () => $middleware->handle($envelope, \throwingStack($exception)))
            ->toThrow(\RuntimeException::class, 'Something went wrong');

        expect($logger->logs)->toHaveCount(2);
        expect($logger->logs[0]['level'])->toBe('info');
        expect($logger->logs[0]['message'])->toBe('Handling message');
        expect($logger->logs[1]['level'])->toBe('error');
        expect($logger->logs[1]['message'])->toBe('Message handling failed');
        expect($logger->logs[1]['context']['error'])->toBe('Something went wrong');
        expect($logger->logs[1]['context'])->toHaveKeys(['message', 'correlation_id', 'error', 'duration_ms']);
    });

    it('uses short class name in log context', function () {
        $logger = \stubLogger();
        $middleware = new LoggingMiddleware($logger, new CorrelationIdProcessor());
        $envelope = new Envelope(new \stdClass());

        $middleware->handle($envelope, \stubStack());

        expect($logger->logs[0]['context']['message'])->toBe('stdClass');
        expect($logger->logs[0]['context']['class'])->toBe('stdClass');
    });

    it('includes duration in milliseconds', function () {
        $logger = \stubLogger();
        $middleware = new LoggingMiddleware($logger, new CorrelationIdProcessor());
        $envelope = new Envelope(new \stdClass());

        $middleware->handle($envelope, \stubStack());

        expect($logger->logs[1]['context']['duration_ms'])->toBeFloat()->toBeGreaterThanOrEqual(0);
    });

    it('uses same correlation ID across handling and handled logs', function () {
        $logger = \stubLogger();
        $middleware = new LoggingMiddleware($logger, new CorrelationIdProcessor());
        $envelope = new Envelope(new \stdClass());

        $middleware->handle($envelope, \stubStack());

        $handlingCorrelationId = $logger->logs[0]['context']['correlation_id'];
        $handledCorrelationId = $logger->logs[1]['context']['correlation_id'];
        expect($handlingCorrelationId)->toBe($handledCorrelationId);
    });
});
