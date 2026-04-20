<?php

declare(strict_types=1);

namespace App\Hub\Shared\Infrastructure\Persistence\Doctrine\Type;

use App\Hub\Shared\Domain\ValueObject\Slug;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class SlugType extends StringType
{
    public const string NAME = 'app_slug';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Slug
    {
        if (!\is_string($value) || $value === '') {
            return null;
        }

        return new Slug($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Slug) {
            return $value->value();
        }

        if (!\is_string($value)) {
            return null;
        }

        return $value;
    }
}
