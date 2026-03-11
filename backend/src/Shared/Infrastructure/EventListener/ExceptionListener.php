<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Shared\Application\DTO\ApiResponse;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = match (true) {
            $exception instanceof NotFoundException => new JsonResponse(
                ApiResponse::error($exception->getMessage(), 404)->toArray(),
                404,
            ),
            $exception instanceof DomainException => new JsonResponse(
                ApiResponse::error($exception->getMessage(), 422)->toArray(),
                422,
            ),
            $exception instanceof ValidationFailedException => $this->handleValidation($exception),
            $exception instanceof HttpException => new JsonResponse(
                ApiResponse::error($exception->getMessage(), $exception->getStatusCode())->toArray(),
                $exception->getStatusCode(),
            ),
            default => null,
        };

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    private function handleValidation(ValidationFailedException $exception): JsonResponse
    {
        $errors = [];
        foreach ($exception->getViolations() as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return new JsonResponse(
            ApiResponse::error('Validation failed.', 422, $errors)->toArray(),
            422,
        );
    }
}
