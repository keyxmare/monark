<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

final readonly class ApiResponse
{
    private function __construct(
        public bool $success,
        public mixed $data,
        public ?ErrorOutput $error,
    ) {
    }

    public static function success(mixed $data = null): self
    {
        return new self(success: true, data: $data, error: null);
    }

    /** @param array<string, string[]> $errors */
    public static function error(string $message, int $code = 400, array $errors = []): self
    {
        return new self(
            success: false,
            data: null,
            error: new ErrorOutput($message, $code, $errors),
        );
    }

    /** @return array{success: bool, data: mixed, error: ?array{message: string, code: int, errors: array<string, mixed>}} */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->error?->toArray(),
        ];
    }
}
