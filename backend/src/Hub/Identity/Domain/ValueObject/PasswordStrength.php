<?php

declare(strict_types=1);

namespace App\Hub\Identity\Domain\ValueObject;

enum PasswordStrength: string
{
    case Weak = 'weak';
    case Fair = 'fair';
    case Strong = 'strong';
}
