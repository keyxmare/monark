<?php

declare(strict_types=1);

use App\Monitoring\Activity\Infrastructure\DoctrineCommitCounter;
use App\Monitoring\Catalog\Domain\Model\Project;
use App\Monitoring\Catalog\Domain\Model\ProjectVisibility;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->counter = self::getContainer()->get(DoctrineCommitCounter::class);
    $this->projectRepo = self::getContainer()->get(ProjectRepositoryInterface::class);
});

/** @param list<int> $series */
function seedProjectWithSeries(ProjectRepositoryInterface $repo, string $slug, array $series): void
{
    $project = Project::create(
        name: $slug,
        slug: $slug,
        description: null,
        repositoryUrl: "https://git.com/{$slug}",
        defaultBranch: 'main',
        visibility: ProjectVisibility::Private,
        ownerId: Uuid::v7(),
    );
    $project->updateActivityCache(null, $series, null, null);
    $repo->save($project);
}

describe('DoctrineCommitCounter', function () {
    it('returns 0 when no project exists', function () {
        expect($this->counter->countDistinctSince(30))->toBe(0);
    });

    it('returns 0 when days <= 0', function () {
        \seedProjectWithSeries($this->projectRepo, 'p1', \array_fill(0, 30, 5));

        expect($this->counter->countDistinctSince(0))->toBe(0);
        expect($this->counter->countDistinctSince(-5))->toBe(0);
    });

    it('ignores projects with an empty series', function () {
        \seedProjectWithSeries($this->projectRepo, 'empty', []);

        expect($this->counter->countDistinctSince(30))->toBe(0);
    });

    it('sums the whole 30-day series across all projects', function () {
        \seedProjectWithSeries($this->projectRepo, 'p1', \array_fill(0, 30, 1));
        \seedProjectWithSeries($this->projectRepo, 'p2', \array_fill(0, 30, 2));

        expect($this->counter->countDistinctSince(30))->toBe(90);
    });

    it('sums only the last N days when days < series length', function () {
        \seedProjectWithSeries($this->projectRepo, 'p1', \range(1, 30));

        expect($this->counter->countDistinctSince(7))->toBe(24 + 25 + 26 + 27 + 28 + 29 + 30);
    });

    it('sums the whole series when days exceeds its length', function () {
        \seedProjectWithSeries($this->projectRepo, 'p1', [3, 4, 5]);

        expect($this->counter->countDistinctSince(30))->toBe(12);
    });
});
