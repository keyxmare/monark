<?php

declare(strict_types=1);

describe('GET /healthz', function () {
    it('returns healthy status', function () {
        $client = static::createClient();
        $client->request('GET', '/healthz');

        expect($client->getResponse()->getStatusCode())->toBe(200);

        $data = \json_decode($client->getResponse()->getContent(), true);
        expect($data['status'])->toBe('healthy');
        expect($data['checks']['database'])->toBe('ok');
    });
});

describe('GET /readyz', function () {
    it('returns ready status', function () {
        $client = static::createClient();
        $client->request('GET', '/readyz');

        expect($client->getResponse()->getStatusCode())->toBe(200);

        $data = \json_decode($client->getResponse()->getContent(), true);
        expect($data['status'])->toBe('ready');
    });
});
