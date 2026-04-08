<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(ProviderRepositoryInterface::class);
});

describe('DoctrineProviderRepository', function () {
    it('saves and finds a provider by id', function () {
        $provider = Provider::create('GitLab', ProviderType::GitLab, 'https://gitlab.com', 'token', null);
        $this->repo->save($provider);

        $found = $this->repo->findById($provider->getId());
        expect($found)->not->toBeNull();
        expect($found->getName())->toBe('GitLab');
    });

    it('lists providers with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(Provider::create("Prov{$i}", ProviderType::GitLab, "https://gl{$i}.com", 'tok', null));
        }

        expect($this->repo->findAll(page: 1, perPage: 2))->toHaveCount(2);
    });

    it('counts providers', function () {
        expect($this->repo->count())->toBe(0);
        $this->repo->save(Provider::create('P', ProviderType::GitLab, 'https://p.com', 'tok', null));
        expect($this->repo->count())->toBe(1);
    });

    it('removes a provider', function () {
        $provider = Provider::create('Del', ProviderType::GitLab, 'https://del.com', 'tok', null);
        $this->repo->save($provider);

        $this->repo->remove($provider);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($provider->getId()))->toBeNull();
    });
});
