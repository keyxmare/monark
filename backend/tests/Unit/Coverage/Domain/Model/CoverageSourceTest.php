<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\Model\CoverageSource;

describe('CoverageSource', function (): void {
    it('has three cases', function (): void {
        expect(CoverageSource::cases())->toHaveCount(3);
    });

    it('maps from GitLab provider type', function (): void {
        expect(CoverageSource::fromProviderType(ProviderType::GitLab))
            ->toBe(CoverageSource::CiGitlab);
    });

    it('maps from GitHub provider type', function (): void {
        expect(CoverageSource::fromProviderType(ProviderType::GitHub))
            ->toBe(CoverageSource::CiGithub);
    });

    it('throws for Bitbucket provider type', function (): void {
        CoverageSource::fromProviderType(ProviderType::Bitbucket);
    })->throws(\LogicException::class, 'Bitbucket coverage not supported yet.');
});
