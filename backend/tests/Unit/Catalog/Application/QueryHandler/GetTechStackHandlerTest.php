<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\TechStackOutput;
use App\Catalog\Application\Query\GetTechStackQuery;
use App\Catalog\Application\QueryHandler\GetTechStackHandler;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\TechStackFactory;

function stubGetTechStackRepo(?TechStack $techStack = null): TechStackRepositoryInterface
{
    return new class ($techStack) implements TechStackRepositoryInterface {
        public function __construct(private readonly ?TechStack $techStack)
        {
        }
        public function findById(Uuid $id): ?TechStack
        {
            return $this->techStack;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function countByProjectId(Uuid $projectId): int
        {
            return 0;
        }
        public function count(): int
        {
            return 0;
        }
        public function save(TechStack $techStack): void
        {
        }
        public function delete(TechStack $techStack): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
        public function findByFramework(string $framework): array
        {
            return [];
        }
        public function findByLanguage(string $language): array
        {
            return [];
        }
    };
}

describe('GetTechStackHandler', function () {
    it('returns a tech stack by id', function () {
        $techStack = TechStackFactory::create(language: 'PHP', framework: 'Symfony');
        $handler = new GetTechStackHandler(\stubGetTechStackRepo($techStack));
        $result = $handler(new GetTechStackQuery($techStack->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(TechStackOutput::class);
        expect($result->language)->toBe('PHP');
        expect($result->framework)->toBe('Symfony');
    });

    it('throws not found when tech stack does not exist', function () {
        $handler = new GetTechStackHandler(\stubGetTechStackRepo(null));
        $handler(new GetTechStackQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
