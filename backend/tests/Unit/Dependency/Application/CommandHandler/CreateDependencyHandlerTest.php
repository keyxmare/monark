<?php

declare(strict_types=1);

use App\Dependency\Application\Command\CreateDependencyCommand;
use App\Dependency\Application\CommandHandler\CreateDependencyHandler;
use App\Dependency\Application\DTO\CreateDependencyInput;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubCreateDependencyRepo(): DependencyRepositoryInterface
{
    return new class () implements DependencyRepositoryInterface {
        public ?Dependency $saved = null;
        public function findById(Uuid $id): ?Dependency { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(Dependency $dependency): void { $this->saved = $dependency; }
        public function delete(Dependency $dependency): void {}
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
        public function deleteByProjectId(Uuid $projectId): void {}
    };
}

describe('CreateDependencyHandler', function () {
    it('creates a dependency successfully', function () {
        $repo = stubCreateDependencyRepo();
        $handler = new CreateDependencyHandler($repo);

        $input = new CreateDependencyInput(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: 'composer',
            type: 'runtime',
            isOutdated: true,
            projectId: '00000000-0000-7000-8000-000000000001',
        );

        $result = $handler(new CreateDependencyCommand($input));

        expect($result)->toBeInstanceOf(DependencyOutput::class);
        expect($result->name)->toBe('symfony/framework-bundle');
        expect($result->currentVersion)->toBe('7.2.0');
        expect($result->latestVersion)->toBe('8.0.0');
        expect($result->packageManager)->toBe('composer');
        expect($result->type)->toBe('runtime');
        expect($result->isOutdated)->toBeTrue();
        expect($repo->saved)->not->toBeNull();
    });
});
