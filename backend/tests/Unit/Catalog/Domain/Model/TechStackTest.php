<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\TechStack;
use Tests\Factory\Catalog\ProjectFactory;
use Tests\Factory\Catalog\TechStackFactory;

describe('TechStack', function () {
    it('creates a tech stack with all fields', function () {
        $project = ProjectFactory::create();
        $detectedAt = new \DateTimeImmutable('2026-03-15T12:00:00+00:00');

        $techStack = TechStack::create(
            language: 'PHP',
            framework: 'Symfony',
            version: '8.4',
            frameworkVersion: '8.0',
            detectedAt: $detectedAt,
            project: $project,
        );

        expect($techStack->getId())->not->toBeNull();
        expect($techStack->getLanguage())->toBe('PHP');
        expect($techStack->getFramework())->toBe('Symfony');
        expect($techStack->getVersion())->toBe('8.4');
        expect($techStack->getFrameworkVersion())->toBe('8.0');
        expect($techStack->getDetectedAt())->toBe($detectedAt);
        expect($techStack->getProject())->toBe($project);
        expect($techStack->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates via factory with defaults', function () {
        $techStack = TechStackFactory::create();

        expect($techStack->getLanguage())->toBe('PHP');
        expect($techStack->getFramework())->toBe('Symfony');
        expect($techStack->getVersion())->toBe('8.4');
        expect($techStack->getFrameworkVersion())->toBe('8.0');
        expect($techStack->getProject())->not->toBeNull();
    });

    it('creates with custom values', function () {
        $techStack = TechStackFactory::create(
            language: 'TypeScript',
            framework: 'Vue.js',
            version: '5.7',
            frameworkVersion: '3.5',
        );

        expect($techStack->getLanguage())->toBe('TypeScript');
        expect($techStack->getFramework())->toBe('Vue.js');
        expect($techStack->getVersion())->toBe('5.7');
        expect($techStack->getFrameworkVersion())->toBe('3.5');
    });
});
