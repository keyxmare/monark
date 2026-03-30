<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Language;
use Tests\Factory\Catalog\LanguageFactory;
use Tests\Factory\Catalog\ProjectFactory;

describe('Language', function () {
    it('creates a language with all fields', function () {
        $project = ProjectFactory::create();
        $detectedAt = new \DateTimeImmutable('2026-03-15T12:00:00+00:00');

        $language = Language::create(
            name: 'PHP',
            version: '8.4',
            detectedAt: $detectedAt,
            project: $project,
        );

        expect($language->getId())->not->toBeNull()
            ->and($language->getName())->toBe('PHP')
            ->and($language->getVersion())->toBe('8.4')
            ->and($language->getDetectedAt())->toBe($detectedAt)
            ->and($language->getProject())->toBe($project)
            ->and($language->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class)
            ->and($language->getUpdatedAt())->toBeInstanceOf(\DateTimeImmutable::class)
            ->and($language->getMaintenanceStatus())->toBeNull()
            ->and($language->getEolDate())->toBeNull();
    });

    it('creates via factory with defaults', function () {
        $language = LanguageFactory::create();

        expect($language->getName())->toBe('PHP')
            ->and($language->getVersion())->toBe('8.4')
            ->and($language->getProject())->not->toBeNull();
    });

    it('creates with custom values', function () {
        $language = LanguageFactory::create(name: 'TypeScript', version: '5.7');

        expect($language->getName())->toBe('TypeScript')
            ->and($language->getVersion())->toBe('5.7');
    });

    it('updates maintenanceStatus and eolDate', function () {
        $language = LanguageFactory::create();
        $eolDate = new \DateTimeImmutable('2027-12-31');

        $language->updateStatus('active', $eolDate);

        expect($language->getMaintenanceStatus())->toBe('active')
            ->and($language->getEolDate())->toBe($eolDate);
    });

    it('clears maintenanceStatus and eolDate when set to null', function () {
        $language = LanguageFactory::create();
        $language->updateStatus('active', new \DateTimeImmutable('2027-12-31'));

        $language->updateStatus(null, null);

        expect($language->getMaintenanceStatus())->toBeNull()
            ->and($language->getEolDate())->toBeNull();
    });

    it('updates updatedAt when updateStatus is called', function () {
        $language = LanguageFactory::create();
        $before = $language->getUpdatedAt();

        $language->updateStatus('eol', null);

        expect($language->getUpdatedAt())->toBeInstanceOf(\DateTimeImmutable::class)
            ->and($language->getUpdatedAt()->getTimestamp())->toBeGreaterThanOrEqual($before->getTimestamp());
    });
});
