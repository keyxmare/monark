<?php

declare(strict_types=1);

use App\Catalog\Domain\Event\FrameworkVersionStatusUpdated;
use App\Catalog\Domain\Model\Framework;
use Tests\Factory\Catalog\FrameworkFactory;
use Tests\Factory\Catalog\LanguageFactory;
use Tests\Factory\Catalog\ProjectFactory;

describe('Framework', function () {
    it('creates a framework with all fields', function () {
        $project = ProjectFactory::create();
        $language = LanguageFactory::create(project: $project);
        $detectedAt = new \DateTimeImmutable('2026-03-15T12:00:00+00:00');

        $framework = Framework::create(
            name: 'Symfony',
            version: '7.1',
            detectedAt: $detectedAt,
            language: $language,
            project: $project,
        );

        expect($framework->getId())->not->toBeNull()
            ->and($framework->getName())->toBe('Symfony')
            ->and($framework->getVersion())->toBe('7.1')
            ->and($framework->getDetectedAt())->toBe($detectedAt)
            ->and($framework->getLanguage())->toBe($language)
            ->and($framework->getProject())->toBe($project)
            ->and($framework->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class)
            ->and($framework->getUpdatedAt())->toBeInstanceOf(\DateTimeImmutable::class)
            ->and($framework->getLatestLts())->toBeNull()
            ->and($framework->getLtsGap())->toBeNull()
            ->and($framework->getMaintenanceStatus())->toBeNull()
            ->and($framework->getEolDate())->toBeNull()
            ->and($framework->getVersionSyncedAt())->toBeNull();
    });

    it('creates via factory with defaults', function () {
        $framework = FrameworkFactory::create();

        expect($framework->getName())->toBe('Symfony')
            ->and($framework->getVersion())->toBe('7.1')
            ->and($framework->getLanguage())->not->toBeNull()
            ->and($framework->getProject())->not->toBeNull();
    });

    it('creates with custom values', function () {
        $framework = FrameworkFactory::create(name: 'Vue.js', version: '3.5');

        expect($framework->getName())->toBe('Vue.js')
            ->and($framework->getVersion())->toBe('3.5');
    });
});

describe('Framework domain events', function () {
    it('emits FrameworkVersionStatusUpdated when updateVersionStatus is called', function () {
        $framework = FrameworkFactory::create();

        $framework->updateVersionStatus(
            latestLts: '7.2.0',
            ltsGap: null,
            maintenanceStatus: 'active',
            eolDate: null,
        );

        $events = $framework->pullDomainEvents();

        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(FrameworkVersionStatusUpdated::class)
            ->and($events[0]->framework)->toBe('Symfony')
            ->and($events[0]->latestLts)->toBe('7.2.0')
            ->and($events[0]->maintenanceStatus)->toBe('active');
    });

    it('clears events after pull', function () {
        $framework = FrameworkFactory::create();
        $framework->updateVersionStatus('7.2.0', null, 'active', null);
        $framework->pullDomainEvents();

        expect($framework->pullDomainEvents())->toBeEmpty();
    });

    it('updates version status fields', function () {
        $framework = FrameworkFactory::create();
        $eolDate = new \DateTimeImmutable('2027-12-31');

        $framework->updateVersionStatus('7.2.0', '1 major', 'active', $eolDate);

        expect($framework->getLatestLts())->toBe('7.2.0')
            ->and($framework->getLtsGap())->toBe('1 major')
            ->and($framework->getMaintenanceStatus())->toBe('active')
            ->and($framework->getEolDate())->toBe($eolDate)
            ->and($framework->getVersionSyncedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('updates updatedAt when updateVersionStatus is called', function () {
        $framework = FrameworkFactory::create();
        $before = $framework->getUpdatedAt();

        $framework->updateVersionStatus('7.2.0', null, 'active', null);

        expect($framework->getUpdatedAt()->getTimestamp())->toBeGreaterThanOrEqual($before->getTimestamp());
    });
});
