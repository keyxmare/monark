<?php

declare(strict_types=1);

use App\Monitoring\Catalog\Domain\Model\Project;
use App\Monitoring\Catalog\Domain\Model\ProjectVisibility;
use App\Monitoring\Catalog\Domain\Model\Provider;
use App\Monitoring\Catalog\Domain\Model\ProviderType;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Monitoring\Catalog\Infrastructure\DoctrineProvidersBreakdown;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->breakdown = self::getContainer()->get(DoctrineProvidersBreakdown::class);
    $this->providerRepo = self::getContainer()->get(ProviderRepositoryInterface::class);
    $this->projectRepo = self::getContainer()->get(ProjectRepositoryInterface::class);
});

function seedProvider(
    ProviderRepositoryInterface $repo,
    ProviderType $type,
    string $name,
    bool $connected = false,
): Provider {
    $provider = Provider::create($name, $type, 'https://example.com/' . $name, 'token', null);
    if ($connected) {
        $provider->markConnected();
    }
    $repo->save($provider);

    return $provider;
}

function seedProjectUnderProvider(
    ProjectRepositoryInterface $repo,
    Provider $provider,
    string $slug,
): void {
    $project = Project::create(
        name: $slug,
        slug: $slug,
        description: null,
        repositoryUrl: "https://git.com/{$slug}",
        defaultBranch: 'main',
        visibility: ProjectVisibility::Private,
        ownerId: Uuid::v7(),
        provider: $provider,
        externalId: $slug,
    );
    $repo->save($project);
}

describe('DoctrineProvidersBreakdown', function () {
    it('returns an empty list when no provider exists', function () {
        expect($this->breakdown->byType())->toBe([]);
    });

    it('reports a single provider with its projects count and connected flag', function () {
        $prov = \seedProvider($this->providerRepo, ProviderType::GitLab, 'Motoblouz', connected: true);
        \seedProjectUnderProvider($this->projectRepo, $prov, 'scryb');
        \seedProjectUnderProvider($this->projectRepo, $prov, 'fluxx');

        expect($this->breakdown->byType())->toBe([
            ['type' => 'gitlab', 'label' => 'GitLab', 'connected' => true, 'projects_count' => 2],
        ]);
    });

    it('shows zero projects when a provider has none attached', function () {
        \seedProvider($this->providerRepo, ProviderType::GitHub, 'empty-gh');

        expect($this->breakdown->byType())->toBe([
            ['type' => 'github', 'label' => 'GitHub', 'connected' => false, 'projects_count' => 0],
        ]);
    });

    it('aggregates multiple providers of the same type', function () {
        $a = \seedProvider($this->providerRepo, ProviderType::GitLab, 'GL-1', connected: true);
        $b = \seedProvider($this->providerRepo, ProviderType::GitLab, 'GL-2', connected: false);
        \seedProjectUnderProvider($this->projectRepo, $a, 'p1');
        \seedProjectUnderProvider($this->projectRepo, $a, 'p2');
        \seedProjectUnderProvider($this->projectRepo, $b, 'p3');

        expect($this->breakdown->byType())->toBe([
            ['type' => 'gitlab', 'label' => 'GitLab', 'connected' => true, 'projects_count' => 3],
        ]);
    });

    it('orders entries by projects count descending', function () {
        $gh = \seedProvider($this->providerRepo, ProviderType::GitHub, 'GH');
        $gl = \seedProvider($this->providerRepo, ProviderType::GitLab, 'GL', connected: true);
        \seedProjectUnderProvider($this->projectRepo, $gl, 'x1');
        \seedProjectUnderProvider($this->projectRepo, $gl, 'x2');
        \seedProjectUnderProvider($this->projectRepo, $gh, 'y1');

        $result = $this->breakdown->byType();

        expect($result[0]['type'])->toBe('gitlab');
        expect($result[0]['projects_count'])->toBe(2);
        expect($result[1]['type'])->toBe('github');
        expect($result[1]['projects_count'])->toBe(1);
    });
});
