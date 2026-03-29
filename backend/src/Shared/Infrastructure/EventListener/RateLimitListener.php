<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final readonly class RateLimitListener
{
    public function __construct(
        private RateLimiterFactory $apiGlobalLimiter,
        private RateLimiterFactory $apiAuthLimiter,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api')) {
            return;
        }

        $clientIp = $request->getClientIp() ?? 'unknown';

        // Stricter limit for auth endpoints
        if (str_contains($path, '/auth/login') || str_contains($path, '/auth/register')) {
            $limiter = $this->apiAuthLimiter->create($clientIp);
        } else {
            $limiter = $this->apiGlobalLimiter->create($clientIp);
        }

        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();
            throw new TooManyRequestsHttpException(
                $retryAfter->getTimestamp() - time(),
                'Too many requests. Please slow down.'
            );
        }
    }
}
