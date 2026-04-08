<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final readonly class HealthController
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[Route('/healthz', name: 'health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return new JsonResponse([
                'status' => 'healthy',
                'checks' => [
                    'database' => 'ok',
                ],
            ]);
        } catch (Throwable) {
            return new JsonResponse([
                'status' => 'unhealthy',
                'checks' => [
                    'database' => 'failed',
                ],
            ], 503);
        }
    }
}
