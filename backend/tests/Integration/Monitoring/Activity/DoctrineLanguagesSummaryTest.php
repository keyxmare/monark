<?php

declare(strict_types=1);

use App\Monitoring\Activity\Infrastructure\DoctrineLanguagesSummary;
use App\Monitoring\Catalog\Domain\Model\Project;
use App\Monitoring\Catalog\Domain\Model\ProjectVisibility;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->summary = self::getContainer()->get(DoctrineLanguagesSummary::class);
    $this->projectRepo = self::getContainer()->get(ProjectRepositoryInterface::class);
});

/** @param array<string, int> $palette */
function seedProjectWithPalette(ProjectRepositoryInterface $repo, string $slug, array $palette): void
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
    $project->updateActivityCache($palette, null, null, null);
    $repo->save($project);
}

describe('DoctrineLanguagesSummary', function () {
    it('returns an empty list when no projects exist', function () {
        expect($this->summary->topLanguages(7))->toBe([]);
    });

    it('returns an empty list when limit <= 0', function () {
        \seedProjectWithPalette($this->projectRepo, 'p1', ['PHP' => 100]);

        expect($this->summary->topLanguages(0))->toBe([]);
        expect($this->summary->topLanguages(-1))->toBe([]);
    });

    it('counts a language once per project regardless of byte volume', function () {
        \seedProjectWithPalette($this->projectRepo, 'tiny', ['PHP' => 1]);
        \seedProjectWithPalette($this->projectRepo, 'huge', ['PHP' => 999_999_999]);

        expect($this->summary->topLanguages(7))->toBe([
            ['name' => 'PHP', 'projects_count' => 2],
        ]);
    });

    it('orders languages by number of projects descending', function () {
        \seedProjectWithPalette($this->projectRepo, 'a', ['PHP' => 100, 'JS' => 50]);
        \seedProjectWithPalette($this->projectRepo, 'b', ['PHP' => 200, 'TS' => 80]);
        \seedProjectWithPalette($this->projectRepo, 'c', ['PHP' => 50]);

        expect($this->summary->topLanguages(7))->toBe([
            ['name' => 'PHP', 'projects_count' => 3],
            ['name' => 'JS', 'projects_count' => 1],
            ['name' => 'TS', 'projects_count' => 1],
        ]);
    });

    it('limits the result to the requested size', function () {
        \seedProjectWithPalette($this->projectRepo, 'a', ['PHP' => 1, 'JS' => 1, 'TS' => 1, 'Go' => 1]);

        expect($this->summary->topLanguages(2))->toHaveCount(2);
    });

    it('ignores projects with an empty palette', function () {
        \seedProjectWithPalette($this->projectRepo, 'empty', []);
        \seedProjectWithPalette($this->projectRepo, 'with-php', ['PHP' => 100]);

        expect($this->summary->topLanguages(7))->toBe([
            ['name' => 'PHP', 'projects_count' => 1],
        ]);
    });

    it('ignores languages with zero or negative bytes', function () {
        \seedProjectWithPalette($this->projectRepo, 'mixed', ['PHP' => 100, 'Ghost' => 0]);

        expect($this->summary->topLanguages(7))->toBe([
            ['name' => 'PHP', 'projects_count' => 1],
        ]);
    });
});
