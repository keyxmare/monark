<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

final readonly class ErrorOutput
{
    public function __construct(
        public string $message,
        public int $code,
        /** @var array<string, mixed> */
        public array $errors = [],
    ) {
    }

    /** @return array{message: string, code: int, errors: array<string, mixed>} */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'code' => $this->code,
            'errors' => $this->errors,
        ];
    }
}
