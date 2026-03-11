<?php

declare(strict_types=1);

namespace App\Activity\Application\Command;

use App\Activity\Application\DTO\CreateNotificationInput;

final readonly class CreateNotificationCommand
{
    public function __construct(
        public CreateNotificationInput $input,
    ) {
    }
}
