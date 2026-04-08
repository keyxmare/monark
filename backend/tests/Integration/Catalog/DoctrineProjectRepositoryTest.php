<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(ProjectRepositoryInterface::class);
    $this->providerRepo = self::getContainer()->get(ProviderRepositoryInterface::class);
});

describe('DoctrineProjectRepository', function () {
    it('saves and finds a project by id', function () {
        $project = Project::create('Test', 'test', 'desc', 'https://git.com/test', 'main', ProjectVisibility::Private, Uuid::v7());
        $this->repo->save($project);

        $found = $this->repo->findById($project->getId());

        expect($found)->not->toBeNull();
        expect($found->getName())->toBe('Test');
        expect($found->getSlug())->toBe('test');
    });

    it('finds a project by slug', function () {
        $project = Project::create('Slug Test', 'slug-test', null, 'https://git.com/slug', 'main', ProjectVisibility::Public, Uuid::v7());
        $this->repo->save($project);

        $found = $this->repo->findBySlug('slug-test');

        expect($found)->not->toBeNull();
        expect($found->getName())->toBe('Slug Test');
    });

    it('returns null for unknown slug', function () {
        expect($this->repo->findBySlug('nonexistent'))->toBeNull();
    });

    it('lists projects with pagination', function () {
        for ($i = 0; $i < 5; $i++) {
            $this->repo->save(
                Project::create("P{$i}", "p-{$i}", null, "https://git.com/{$i}", 'main', ProjectVisibility::Private, Uuid::v7())
            );
        }

        $page1 = $this->repo->findAll(page: 1, perPage: 3);
        expect($page1)->toHaveCount(3);

        $page2 = $this->repo->findAll(page: 2, perPage: 3);
        expect($page2)->toHaveCount(2);
    });

    it('counts projects', function () {
        expect($this->repo->count())->toBe(0);

        $this->repo->save(Project::create('A', 'a', null, 'https://git.com/a', 'main', ProjectVisibility::Private, Uuid::v7()));
        expect($this->repo->count())->toBe(1);
    });

    it('deletes a project', function () {
        $project = Project::create('Del', 'del', null, 'https://git.com/del', 'main', ProjectVisibility::Private, Uuid::v7());
        $this->repo->save($project);

        $this->repo->delete($project);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($project->getId()))->toBeNull();
    });

    it('finds projects by provider id', function () {
        $provider = Provider::create('GL', ProviderType::GitLab, 'https://gl.com', 'token', null);
        $this->providerRepo->save($provider);

        $project = Project::create('WithProv', 'with-prov', null, 'https://git.com/wp', 'main', ProjectVisibility::Private, Uuid::v7(), $provider, '123');
        $this->repo->save($project);

        $found = $this->repo->findByProviderId($provider->getId());
        expect($found)->toHaveCount(1);
        expect($found[0]->getSlug())->toBe('with-prov');
    });

    it('finds by external id and provider', function () {
        $provider = Provider::create('GL2', ProviderType::GitLab, 'https://gl2.com', 'token', null);
        $this->providerRepo->save($provider);

        $project = Project::create('Ext', 'ext', null, 'https://git.com/ext', 'main', ProjectVisibility::Private, Uuid::v7(), $provider, 'ext-456');
        $this->repo->save($project);

        $found = $this->repo->findByExternalIdAndProvider('ext-456', $provider->getId());
        expect($found)->not->toBeNull();
        expect($found->getSlug())->toBe('ext');
    });

    it('builds external id map by provider', function () {
        $provider = Provider::create('GL3', ProviderType::GitLab, 'https://gl3.com', 'token', null);
        $this->providerRepo->save($provider);

        $p1 = Project::create('M1', 'm-1', null, 'https://git.com/m1', 'main', ProjectVisibility::Private, Uuid::v7(), $provider, 'ext-1');
        $this->repo->save($p1);

        $p2 = Project::create('M2', 'm-2', null, 'https://git.com/m2', 'main', ProjectVisibility::Private, Uuid::v7(), $provider, 'ext-2');
        $this->repo->save($p2);

        $map = $this->repo->findExternalIdMapByProvider($provider->getId());
        expect($map)->toHaveCount(2);
        expect($map)->toHaveKey('ext-1');
        expect($map)->toHaveKey('ext-2');
    });

    it('finds all projects with provider', function () {
        $provider = Provider::create('GL4', ProviderType::GitLab, 'https://gl4.com', 'token', null);
        $this->providerRepo->save($provider);

        $withProvider = Project::create('WP', 'wp', null, 'https://git.com/wp', 'main', ProjectVisibility::Private, Uuid::v7(), $provider, 'ext-99');
        $this->repo->save($withProvider);

        $without = Project::create('NP', 'np', null, 'https://git.com/np', 'main', ProjectVisibility::Private, Uuid::v7());
        $this->repo->save($without);

        $found = $this->repo->findAllWithProvider();
        expect($found)->toHaveCount(1);
    });
});
