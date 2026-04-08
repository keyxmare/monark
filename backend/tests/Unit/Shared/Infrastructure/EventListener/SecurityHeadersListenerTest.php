<?php

declare(strict_types=1);

use App\Shared\Infrastructure\EventListener\SecurityHeadersListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

function stubKernel(): HttpKernelInterface
{
    return new class () implements HttpKernelInterface {
        public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
        {
            return new Response();
        }
    };
}

function mainRequestEvent(): ResponseEvent
{
    return new ResponseEvent(\stubKernel(), Request::create('/'), HttpKernelInterface::MAIN_REQUEST, new Response());
}

function subRequestEvent(): ResponseEvent
{
    return new ResponseEvent(\stubKernel(), Request::create('/'), HttpKernelInterface::SUB_REQUEST, new Response());
}

describe('SecurityHeadersListener', function () {
    it('adds X-Content-Type-Options header on main request', function () {
        $event = \mainRequestEvent();

        (new SecurityHeadersListener())($event);

        expect($event->getResponse()->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    });

    it('adds X-Frame-Options header on main request', function () {
        $event = \mainRequestEvent();

        (new SecurityHeadersListener())($event);

        expect($event->getResponse()->headers->get('X-Frame-Options'))->toBe('DENY');
    });

    it('adds X-XSS-Protection header on main request', function () {
        $event = \mainRequestEvent();

        (new SecurityHeadersListener())($event);

        expect($event->getResponse()->headers->get('X-XSS-Protection'))->toBe('1; mode=block');
    });

    it('adds Referrer-Policy header on main request', function () {
        $event = \mainRequestEvent();

        (new SecurityHeadersListener())($event);

        expect($event->getResponse()->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    });

    it('adds Strict-Transport-Security header on main request', function () {
        $event = \mainRequestEvent();

        (new SecurityHeadersListener())($event);

        expect($event->getResponse()->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000; includeSubDomains');
    });

    it('adds Permissions-Policy header on main request', function () {
        $event = \mainRequestEvent();

        (new SecurityHeadersListener())($event);

        expect($event->getResponse()->headers->get('Permissions-Policy'))->toBe('camera=(), microphone=(), geolocation=(), payment=()');
    });

    it('adds Content-Security-Policy header on main request', function () {
        $event = \mainRequestEvent();

        (new SecurityHeadersListener())($event);

        $csp = $event->getResponse()->headers->get('Content-Security-Policy');
        expect($csp)->toContain("default-src 'self'");
        expect($csp)->toContain("script-src 'self'");
        expect($csp)->toContain("frame-ancestors 'none'");
    });

    it('skips all headers on sub-request', function () {
        $event = \subRequestEvent();

        (new SecurityHeadersListener())($event);

        $headers = $event->getResponse()->headers;
        expect($headers->has('X-Content-Type-Options'))->toBeFalse();
        expect($headers->has('X-Frame-Options'))->toBeFalse();
        expect($headers->has('X-XSS-Protection'))->toBeFalse();
        expect($headers->has('Referrer-Policy'))->toBeFalse();
        expect($headers->has('Strict-Transport-Security'))->toBeFalse();
        expect($headers->has('Permissions-Policy'))->toBeFalse();
        expect($headers->has('Content-Security-Policy'))->toBeFalse();
    });

    it('sets all seven security headers on main request', function () {
        $event = \mainRequestEvent();

        (new SecurityHeadersListener())($event);

        $headers = $event->getResponse()->headers;
        $expected = [
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection',
            'Referrer-Policy',
            'Strict-Transport-Security',
            'Permissions-Policy',
            'Content-Security-Policy',
        ];

        foreach ($expected as $header) {
            expect($headers->has($header))->toBeTrue();
        }
    });
});
