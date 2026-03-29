<?php

declare(strict_types=1);

use App\Catalog\Application\EventListener\UpdateTechStackVersionStatusListener;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function makeTechStack(string $language, string $framework, string $version, string $frameworkVersion): TechStack
{
    $project = Project::create(
        name: 'Test Project',
        slug: 'test-project-' . \uniqid(),
        description: null,
        repositoryUrl: 'https://github.com/test/test',
        defaultBranch: 'main',
        visibility: ProjectVisibility::Public,
        ownerId: Uuid::v7(),
    );

    return TechStack::create(
        language: $language,
        framework: $framework,
        version: $version,
        frameworkVersion: $frameworkVersion,
        detectedAt: new DateTimeImmutable(),
        project: $project,
    );
}

describe('UpdateTechStackVersionStatusListener', function () {
    it('skips events that have a packageManager (those belong to dependency listener)', function () {
        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->expects($this->never())->method('findByFramework');
        $repo->expects($this->never())->method('findByLanguage');
        $repo->expects($this->never())->method('save');

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony/symfony',
            packageManager: PackageManager::Composer,
            latestVersion: '7.2.0',
            ltsVersion: null,
        );

        $listener = new UpdateTechStackVersionStatusListener($repo);
        $listener($event);
    });

    it('calls findByFramework and updates version status for a known framework', function () {
        $ts = makeTechStack('PHP', 'Symfony', '8.2.0', '6.4.0');

        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->method('findByFramework')->with('Symfony')->willReturn([$ts]);
        $repo->expects($this->once())->method('save')->with($ts);

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: '7.2.0',
            ltsVersion: '7.1.0',
            eolCycles: [],
        );

        $listener = new UpdateTechStackVersionStatusListener($repo);
        $listener($event);

        expect($ts->getLatestLts())->toBe('7.1.0')
            ->and($ts->getLtsGap())->toBe('6.4.0 → 7.1.0')
            ->and($ts->getMaintenanceStatus())->toBe('active')
            ->and($ts->getVersionSyncedAt())->not->toBeNull();
    });

    it('calls findByLanguage for a known language and updates version status', function () {
        $ts = makeTechStack('PHP', 'Symfony', '8.1.0', '6.0.0');

        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->method('findByLanguage')->with('PHP')->willReturn([$ts]);
        $repo->expects($this->once())->method('save')->with($ts);

        $event = new ProductVersionsSyncedEvent(
            productName: 'php',
            packageManager: null,
            latestVersion: '8.3.0',
            ltsVersion: null,
            eolCycles: [],
        );

        $listener = new UpdateTechStackVersionStatusListener($repo);
        $listener($event);

        expect($ts->getLatestLts())->toBe('8.3.0')
            ->and($ts->getMaintenanceStatus())->toBe('active')
            ->and($ts->getVersionSyncedAt())->not->toBeNull();
    });

    it('marks status as eol when eolDate is "true"', function () {
        $ts = makeTechStack('PHP', 'Symfony', '8.0.0', '4.4.0');

        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->method('findByFramework')->with('Symfony')->willReturn([$ts]);
        $repo->expects($this->once())->method('save');

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: '7.2.0',
            ltsVersion: '6.4.0',
            eolCycles: [
                ['version' => '4.4', 'eolDate' => 'true', 'isLts' => true],
            ],
        );

        $listener = new UpdateTechStackVersionStatusListener($repo);
        $listener($event);

        expect($ts->getMaintenanceStatus())->toBe('eol')
            ->and($ts->getLtsGap())->toBe('4.4.0 → 6.4.0')
            ->and($ts->getEolDate())->toBeNull();
    });

    it('marks status as eol when eolDate is in the past', function () {
        $ts = makeTechStack('PHP', 'Symfony', '8.0.0', '5.4.0');

        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->method('findByFramework')->with('Symfony')->willReturn([$ts]);
        $repo->expects($this->once())->method('save');

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: '7.2.0',
            ltsVersion: null,
            eolCycles: [
                ['version' => '5.4', 'eolDate' => '2022-11-01', 'isLts' => true],
            ],
        );

        $listener = new UpdateTechStackVersionStatusListener($repo);
        $listener($event);

        expect($ts->getMaintenanceStatus())->toBe('eol')
            ->and($ts->getEolDate())->not->toBeNull()
            ->and($ts->getEolDate()->format('Y-m-d'))->toBe('2022-11-01');
    });

    it('skips unknown products without calling repo methods', function () {
        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->expects($this->never())->method('findByFramework');
        $repo->expects($this->never())->method('findByLanguage');
        $repo->expects($this->never())->method('save');

        $event = new ProductVersionsSyncedEvent(
            productName: 'unknown-product',
            packageManager: null,
            latestVersion: '1.0.0',
            ltsVersion: null,
        );

        $listener = new UpdateTechStackVersionStatusListener($repo);
        $listener($event);
    });

    it('handles nodejs mapping to multiple language names', function () {
        $ts1 = makeTechStack('JavaScript', 'React', '16.0.0', '18.0.0');
        $ts2 = makeTechStack('Node.js', 'Express', '18.0.0', '4.18.0');

        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->method('findByLanguage')->willReturnMap([
            ['JavaScript', [$ts1]],
            ['TypeScript', []],
            ['Node.js', [$ts2]],
        ]);
        $repo->expects($this->exactly(2))->method('save');

        $event = new ProductVersionsSyncedEvent(
            productName: 'nodejs',
            packageManager: null,
            latestVersion: '22.0.0',
            ltsVersion: '20.11.0',
        );

        $listener = new UpdateTechStackVersionStatusListener($repo);
        $listener($event);

        expect($ts1->getVersionSyncedAt())->not->toBeNull()
            ->and($ts2->getVersionSyncedAt())->not->toBeNull();
    });

    it('sets no gap when current version is already at or above lts', function () {
        $ts = makeTechStack('PHP', 'Symfony', '8.2.0', '7.2.0');

        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->method('findByFramework')->with('Symfony')->willReturn([$ts]);
        $repo->expects($this->once())->method('save');

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: '7.2.0',
            ltsVersion: '7.1.0',
            eolCycles: [],
        );

        $listener = new UpdateTechStackVersionStatusListener($repo);
        $listener($event);

        expect($ts->getLtsGap())->toBeNull()
            ->and($ts->getMaintenanceStatus())->toBe('active');
    });
});
