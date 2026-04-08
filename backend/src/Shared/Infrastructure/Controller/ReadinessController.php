<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ReadinessController
{
    #[Route('/readyz', name: 'readiness', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ready',
        ]);
    }
}
