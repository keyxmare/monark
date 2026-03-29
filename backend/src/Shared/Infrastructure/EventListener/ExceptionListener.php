<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Shared\Application\DTO\ApiResponse;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ExceptionListener
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HandlerFailedException) {
            $exception = $exception->getPrevious() ?? $exception;
        }

        $response = match (true) {
            $exception instanceof NotFoundException => new JsonResponse(
                ApiResponse::error(
                    $this->translator->trans('error.entity_not_found', [
                        '%entity%' => $exception->entity,
                        '%id%' => $exception->entityId,
                    ]),
                    404,
                )->toArray(),
                404,
            ),
            $exception instanceof DomainException => new JsonResponse(
                ApiResponse::error($this->translateDomainMessage($exception->getMessage()), 422)->toArray(),
                422,
            ),
            $exception instanceof ValidationFailedException => $this->handleValidation(),
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

    private function handleValidation(): JsonResponse
    {
        return new JsonResponse(
            ApiResponse::error($this->translator->trans('error.validation'), 422)->toArray(),
            422,
        );
    }

    private function translateDomainMessage(string $message): string
    {
        $keyMap = [
            'A user with this email already exists.' => 'error.duplicate_email',
            'A project with this slug already exists.' => 'error.duplicate_slug',
            'Invalid credentials.' => 'error.invalid_credentials',
        ];

        $key = $keyMap[$message] ?? null;

        if ($key !== null) {
            return $this->translator->trans($key);
        }

        if (\str_contains($message, 'is not linked to a provider')) {
            return $this->translator->trans('error.project_not_linked');
        }

        return $message;
    }
}
