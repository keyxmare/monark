<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(TechStackRepositoryInterface::class);
    $this->projectRepo = self::getContainer()->get(ProjectRepositoryInterface::class);

    $this->project = Project::create('P', 'p', null, 'https://git.com/p', 'main', ProjectVisibility::Private, Uuid::v7());
    $this->projectRepo->save($this->project);
});

describe('DoctrineTechStackRepository', function () {
    it('saves and finds by id', function () {
        $ts = TechStack::create('PHP', 'Symfony', '8.4', '8.0', new DateTimeImmutable(), $this->project);
        $this->repo->save($ts);

        $found = $this->repo->findById($ts->getId());
        expect($found)->not->toBeNull();
        expect($found->getLanguage())->toBe('PHP');
    });

    it('finds by project id with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(TechStack::create("Lang{$i}", 'FW', '1.0', '1.0', new DateTimeImmutable(), $this->project));
        }

        expect($this->repo->findByProjectId($this->project->getId(), 1, 2))->toHaveCount(2);
    });

    it('counts by project id', function () {
        $this->repo->save(TechStack::create('PHP', 'Symfony', '8.4', '8.0', new DateTimeImmutable(), $this->project));
        expect($this->repo->countByProjectId($this->project->getId()))->toBe(1);
    });

    it('deletes by project id', function () {
        $this->repo->save(TechStack::create('PHP', 'SF', '8.4', '8.0', new DateTimeImmutable(), $this->project));
        $this->repo->save(TechStack::create('JS', 'React', '18', '18', new DateTimeImmutable(), $this->project));

        $this->repo->deleteByProjectId($this->project->getId());
        $this->getEntityManager()->clear();

        expect($this->repo->countByProjectId($this->project->getId()))->toBe(0);
    });

    it('deletes a single tech stack', function () {
        $ts = TechStack::create('Go', 'None', '1.22', '', new DateTimeImmutable(), $this->project);
        $this->repo->save($ts);

        $this->repo->delete($ts);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($ts->getId()))->toBeNull();
    });
});
