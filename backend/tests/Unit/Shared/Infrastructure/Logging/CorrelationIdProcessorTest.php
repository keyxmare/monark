<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Logging\CorrelationIdProcessor;
use Monolog\Level;
use Monolog\LogRecord;

describe('CorrelationIdProcessor', function () {
    it('does not add correlation_id when none is set', function () {
        $processor = new CorrelationIdProcessor();
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test message',
        );

        $result = $processor($record);

        expect($result->extra)->not->toHaveKey('correlation_id');
    });

    it('adds correlation_id to extra when set', function () {
        $processor = new CorrelationIdProcessor();
        $processor->setCorrelationId('test-correlation-id');

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test message',
        );

        $result = $processor($record);

        expect($result->extra)->toHaveKey('correlation_id');
        expect($result->extra['correlation_id'])->toBe('test-correlation-id');
    });

    it('preserves existing extra fields', function () {
        $processor = new CorrelationIdProcessor();
        $processor->setCorrelationId('abc-123');

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test message',
            extra: ['existing_key' => 'existing_value'],
        );

        $result = $processor($record);

        expect($result->extra)->toHaveKey('existing_key');
        expect($result->extra['existing_key'])->toBe('existing_value');
        expect($result->extra['correlation_id'])->toBe('abc-123');
    });
});
