<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateNotificationInput
{
    public function __construct(
        #[Assert\NotBlank]
        public string $title,

        #[Assert\NotBlank]
        public string $message,

        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['in_app', 'email'])]
        public string $channel,

        #[Assert\NotBlank]
        public string $userId = '',
    ) {
    }
}
