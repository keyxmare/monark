<?php

declare(strict_types=1);

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Infrastructure\EventListener\ExceptionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

function stubTranslator(): TranslatorInterface
{
    return new class implements TranslatorInterface {
        public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
        {
            return match ($id) {
                'error.entity_not_found' => \sprintf('Entity %s not found (%s)', $parameters['%entity%'] ?? '', $parameters['%id%'] ?? ''),
                'error.validation' => 'Validation failed',
                'error.duplicate_email' => 'Email already exists',
                'error.duplicate_slug' => 'Slug already exists',
                'error.invalid_credentials' => 'Invalid credentials',
                'error.project_not_linked' => 'Project not linked',
                default => $id,
            };
        }

        public function getLocale(): string
        {
            return 'en';
        }
    };
}

function createExceptionEvent(\Throwable $exception): ExceptionEvent
{
    $kernel = new class implements HttpKernelInterface {
        public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): \Symfony\Component\HttpFoundation\Response
        {
            return new \Symfony\Component\HttpFoundation\Response();
        }
    };

    return new ExceptionEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $exception);
}

it('handles NotFoundException with 404', function () {
    $listener = new ExceptionListener(stubTranslator());
    $event = createExceptionEvent(NotFoundException::forEntity('User', 'u-1'));

    $listener($event);

    $response = $event->getResponse();
    expect($response)->not->toBeNull();
    expect($response->getStatusCode())->toBe(404);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeFalse();
});

it('handles DomainException with known translation key', function () {
    $listener = new ExceptionListener(stubTranslator());
    $exception = new class ('A user with this email already exists.') extends DomainException {};
    $event = createExceptionEvent($exception);

    $listener($event);

    $response = $event->getResponse();
    expect($response->getStatusCode())->toBe(422);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['error']['message'])->toBe('Email already exists');
});

it('handles DomainException with project not linked message', function () {
    $listener = new ExceptionListener(stubTranslator());
    $exception = new class ('Project X is not linked to a provider') extends DomainException {};
    $event = createExceptionEvent($exception);

    $listener($event);

    $response = $event->getResponse();
    expect($response->getStatusCode())->toBe(422);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['error']['message'])->toBe('Project not linked');
});

it('handles DomainException with unknown message passthrough', function () {
    $listener = new ExceptionListener(stubTranslator());
    $exception = new class ('Some unknown domain error') extends DomainException {};
    $event = createExceptionEvent($exception);

    $listener($event);

    $response = $event->getResponse();
    expect($response->getStatusCode())->toBe(422);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['error']['message'])->toBe('Some unknown domain error');
});

it('handles HttpException', function () {
    $listener = new ExceptionListener(stubTranslator());
    $event = createExceptionEvent(new HttpException(403, 'Forbidden'));

    $listener($event);

    $response = $event->getResponse();
    expect($response->getStatusCode())->toBe(403);
});

it('handles ValidationFailedException with 422', function () {
    $listener = new ExceptionListener(stubTranslator());
    $violations = new \Symfony\Component\Validator\ConstraintViolationList();
    $event = createExceptionEvent(new \Symfony\Component\Validator\Exception\ValidationFailedException('value', $violations));

    $listener($event);

    $response = $event->getResponse();
    expect($response->getStatusCode())->toBe(422);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['error']['message'])->toBe('Validation failed');
});

it('handles DomainException with duplicate slug for team', function () {
    $listener = new ExceptionListener(stubTranslator());
    $exception = new class ('A team with this slug already exists.') extends DomainException {};
    $event = createExceptionEvent($exception);

    $listener($event);

    $response = $event->getResponse();
    expect($response->getStatusCode())->toBe(422);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['error']['message'])->toBe('Slug already exists');
});

it('handles DomainException with duplicate slug for project', function () {
    $listener = new ExceptionListener(stubTranslator());
    $exception = new class ('A project with this slug already exists.') extends DomainException {};
    $event = createExceptionEvent($exception);

    $listener($event);

    $response = $event->getResponse();
    expect($response->getStatusCode())->toBe(422);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['error']['message'])->toBe('Slug already exists');
});

it('handles DomainException with duplicate slug for quiz', function () {
    $listener = new ExceptionListener(stubTranslator());
    $exception = new class ('A quiz with this slug already exists.') extends DomainException {};
    $event = createExceptionEvent($exception);

    $listener($event);

    $response = $event->getResponse();
    expect($response->getStatusCode())->toBe(422);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['error']['message'])->toBe('Slug already exists');
});

it('handles DomainException with invalid credentials', function () {
    $listener = new ExceptionListener(stubTranslator());
    $exception = new class ('Invalid credentials.') extends DomainException {};
    $event = createExceptionEvent($exception);

    $listener($event);

    $response = $event->getResponse();
    expect($response->getStatusCode())->toBe(422);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['error']['message'])->toBe('Invalid credentials');
});

it('includes entity details in NotFoundException response', function () {
    $listener = new ExceptionListener(stubTranslator());
    $event = createExceptionEvent(NotFoundException::forEntity('Project', 'p-42'));

    $listener($event);

    $data = \json_decode((string) $event->getResponse()->getContent(), true);
    expect($data['error']['message'])->toContain('Project');
    expect($data['error']['message'])->toContain('p-42');
    expect($data['error']['code'])->toBe(404);
});

it('includes status code in HttpException response body', function () {
    $listener = new ExceptionListener(stubTranslator());
    $event = createExceptionEvent(new HttpException(429, 'Too many requests'));

    $listener($event);

    $response = $event->getResponse();
    expect($response->getStatusCode())->toBe(429);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['error']['message'])->toBe('Too many requests');
    expect($data['error']['code'])->toBe(429);
});

it('does not handle generic exceptions', function () {
    $listener = new ExceptionListener(stubTranslator());
    $event = createExceptionEvent(new \RuntimeException('Unexpected'));

    $listener($event);

    expect($event->getResponse())->toBeNull();
});
