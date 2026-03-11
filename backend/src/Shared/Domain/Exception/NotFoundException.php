<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class NotFoundException extends DomainException
{
    private function __construct(
        string $message,
        public readonly string $entity,
        public readonly string $entityId,
    ) {
        parent::__construct($message);
    }

    public static function forEntity(string $entity, string $id): self
    {
        return new self(
            \sprintf('%s with id "%s" was not found.', $entity, $id),
            $entity,
            $id,
        );
    }
}
