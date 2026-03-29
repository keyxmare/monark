<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Domain\ValueObject\RepositoryUrl;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class RepositoryUrlType extends StringType
{
    public const string NAME = 'app_repository_url';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RepositoryUrl
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new RepositoryUrl((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof RepositoryUrl) {
            return $value->value();
        }

        return (string) $value;
    }
}
