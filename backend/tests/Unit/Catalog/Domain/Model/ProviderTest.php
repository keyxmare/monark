<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderStatus;
use App\Catalog\Domain\Model\ProviderType;
use Tests\Factory\Catalog\ProviderFactory;

describe('Provider', function () {
    it('creates a provider with all fields', function () {
        $provider = Provider::create(
            name: 'GitLab Corp',
            type: ProviderType::GitLab,
            url: 'https://gitlab.example.com/',
            apiToken: 'glpat-secret',
            username: 'admin',
        );

        expect($provider->getId())->not->toBeNull();
        expect($provider->getName())->toBe('GitLab Corp');
        expect($provider->getType())->toBe(ProviderType::GitLab);
        expect($provider->getUrl())->toBe('https://gitlab.example.com');
        expect($provider->getApiToken())->toBe('glpat-secret');
        expect($provider->getUsername())->toBe('admin');
        expect($provider->getStatus())->toBe(ProviderStatus::Pending);
        expect($provider->getLastSyncAt())->toBeNull();
        expect($provider->getProjects())->toBeEmpty();
        expect($provider->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($provider->getUpdatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates a provider with nullable fields', function () {
        $provider = Provider::create(
            name: 'GitHub',
            type: ProviderType::GitHub,
            url: 'https://github.com',
        );

        expect($provider->getApiToken())->toBeNull();
        expect($provider->getUsername())->toBeNull();
    });

    it('trims trailing slash from url', function () {
        $provider = Provider::create(
            name: 'Test',
            type: ProviderType::GitLab,
            url: 'https://gitlab.com/',
        );

        expect($provider->getUrl())->toBe('https://gitlab.com');
    });

    it('updates fields selectively', function () {
        $provider = ProviderFactory::create();
        $beforeUpdate = $provider->getUpdatedAt();
        \usleep(1000);

        $provider->update(name: 'Updated Name');

        expect($provider->getName())->toBe('Updated Name');
        expect($provider->getUrl())->toBe('https://gitlab.example.com');
        expect($provider->getUpdatedAt())->not->toEqual($beforeUpdate);
    });

    it('updates all fields', function () {
        $provider = ProviderFactory::create();

        $provider->update(
            name: 'New Name',
            url: 'https://new.gitlab.com/',
            apiToken: 'new-token',
            username: 'newuser',
        );

        expect($provider->getName())->toBe('New Name');
        expect($provider->getUrl())->toBe('https://new.gitlab.com');
        expect($provider->getApiToken())->toBe('new-token');
        expect($provider->getUsername())->toBe('newuser');
    });

    it('marks as connected', function () {
        $provider = ProviderFactory::create();

        expect($provider->getStatus())->toBe(ProviderStatus::Pending);

        $beforeUpdate = $provider->getUpdatedAt();
        \usleep(1000);
        $provider->markConnected();

        expect($provider->getStatus())->toBe(ProviderStatus::Connected);
        expect($provider->getLastSyncAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($provider->getUpdatedAt())->not->toEqual($beforeUpdate);
    });

    it('marks as error', function () {
        $provider = ProviderFactory::create();

        $beforeUpdate = $provider->getUpdatedAt();
        \usleep(1000);
        $provider->markError();

        expect($provider->getStatus())->toBe(ProviderStatus::Error);
        expect($provider->getUpdatedAt())->not->toEqual($beforeUpdate);
    });
});
