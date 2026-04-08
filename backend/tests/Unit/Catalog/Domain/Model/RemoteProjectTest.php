<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\RemoteProject;

describe('RemoteProject', function () {
    it('creates a remote project with all fields', function () {
        $remoteProject = new RemoteProject(
            externalId: '42',
            name: 'Monark',
            slug: 'monark',
            description: 'Hub développeur',
            repositoryUrl: 'https://gitlab.com/team/monark',
            defaultBranch: 'main',
            visibility: 'public',
            avatarUrl: 'https://gitlab.com/uploads/avatar.png',
        );

        expect($remoteProject->externalId)->toBe('42');
        expect($remoteProject->name)->toBe('Monark');
        expect($remoteProject->slug)->toBe('monark');
        expect($remoteProject->description)->toBe('Hub développeur');
        expect($remoteProject->repositoryUrl)->toBe('https://gitlab.com/team/monark');
        expect($remoteProject->defaultBranch)->toBe('main');
        expect($remoteProject->visibility)->toBe('public');
        expect($remoteProject->avatarUrl)->toBe('https://gitlab.com/uploads/avatar.png');
    });

    it('creates with nullable fields', function () {
        $remoteProject = new RemoteProject(
            externalId: '1',
            name: 'Test',
            slug: 'test',
            description: null,
            repositoryUrl: 'https://github.com/test/repo',
            defaultBranch: 'main',
            visibility: 'private',
            avatarUrl: null,
        );

        expect($remoteProject->description)->toBeNull();
        expect($remoteProject->avatarUrl)->toBeNull();
    });
});
