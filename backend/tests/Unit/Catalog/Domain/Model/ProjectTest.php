<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\ProjectVisibility;
use Tests\Factory\Catalog\ProjectFactory;
use Tests\Factory\Catalog\ProviderFactory;

describe('Project', function () {
    it('creates a project with all fields', function () {
        $ownerId = \Symfony\Component\Uid\Uuid::v7();
        $provider = ProviderFactory::create();

        $project = \App\Catalog\Domain\Model\Project::create(
            name: 'Monark',
            slug: 'monark',
            description: 'Hub développeur',
            repositoryUrl: 'https://gitlab.com/team/monark',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Public,
            ownerId: $ownerId,
            provider: $provider,
            externalId: 'ext-123',
        );

        expect($project->getId())->not->toBeNull();
        expect($project->getName())->toBe('Monark');
        expect($project->getSlug())->toBe('monark');
        expect($project->getDescription())->toBe('Hub développeur');
        expect($project->getRepositoryUrl())->toBe('https://gitlab.com/team/monark');
        expect($project->getDefaultBranch())->toBe('main');
        expect($project->getVisibility())->toBe(ProjectVisibility::Public);
        expect($project->getOwnerId())->toBe($ownerId);
        expect($project->getProvider())->toBe($provider);
        expect($project->getExternalId())->toBe('ext-123');
        expect($project->getTechStacks())->toBeEmpty();
        expect($project->getMergeRequests())->toBeEmpty();
        expect($project->getLastSyncedAt())->toBeNull();
        expect($project->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($project->getUpdatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates a project with nullable fields', function () {
        $project = ProjectFactory::create(description: null);

        expect($project->getDescription())->toBeNull();
        expect($project->getProvider())->toBeNull();
        expect($project->getExternalId())->toBeNull();
    });

    it('updates fields selectively', function () {
        $project = ProjectFactory::create();
        $beforeUpdate = $project->getUpdatedAt();
        \usleep(1000);

        $project->update(
            name: 'New Name',
            visibility: ProjectVisibility::Public,
        );

        expect($project->getName())->toBe('New Name');
        expect($project->getVisibility())->toBe(ProjectVisibility::Public);
        expect($project->getSlug())->toBe('my-project');
        expect($project->getUpdatedAt())->not->toEqual($beforeUpdate);
    });

    it('updates all fields', function () {
        $project = ProjectFactory::create();

        $project->update(
            name: 'Updated',
            slug: 'updated-slug',
            description: 'Updated desc',
            repositoryUrl: 'https://github.com/new/repo',
            defaultBranch: 'develop',
            visibility: ProjectVisibility::Public,
        );

        expect($project->getName())->toBe('Updated');
        expect($project->getSlug())->toBe('updated-slug');
        expect($project->getDescription())->toBe('Updated desc');
        expect($project->getRepositoryUrl())->toBe('https://github.com/new/repo');
        expect($project->getDefaultBranch())->toBe('develop');
        expect($project->getVisibility())->toBe(ProjectVisibility::Public);
    });

    it('marks as synced', function () {
        $project = ProjectFactory::create();

        expect($project->getLastSyncedAt())->toBeNull();

        $beforeUpdate = $project->getUpdatedAt();
        \usleep(1000);
        $project->markSynced();

        expect($project->getLastSyncedAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($project->getUpdatedAt())->not->toEqual($beforeUpdate);
    });

    it('rejects blank name', function () {
        expect(fn () => ProjectFactory::create(name: ''))
            ->toThrow(\InvalidArgumentException::class, 'name must not be blank');
    });

    it('rejects whitespace-only name', function () {
        expect(fn () => ProjectFactory::create(name: '   '))
            ->toThrow(\InvalidArgumentException::class, 'name must not be blank');
    });

    it('rejects blank default branch', function () {
        expect(fn () => ProjectFactory::create(defaultBranch: ''))
            ->toThrow(\InvalidArgumentException::class, 'default branch must not be blank');
    });
});
