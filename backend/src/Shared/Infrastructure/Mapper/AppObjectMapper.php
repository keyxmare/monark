<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Mapper;

use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class AppObjectMapper
{
    public function __construct(
        private ObjectMapperInterface $objectMapper,
    ) {
    }

    public function map(object $source, object|string $target): object
    {
        return $this->objectMapper->map($source, $target);
    }
}
