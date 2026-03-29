<?php

declare(strict_types=1);

use App\Catalog\Application\Command\DeleteTechStackCommand;
use App\Catalog\Application\CommandHandler\DeleteTechStackHandler;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\TechStackFactory;

function stubDeleteTechStackRepo(?TechStack $techStack = null): TechStackRepositoryInterface
{
    return new class ($techStack) implements TechStackRepositoryInterface {
        public bool $deleted = false;
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
            $this->deleted = true;
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

describe('DeleteTechStackHandler', function () {
    it('deletes a tech stack successfully', function () {
        $techStack = TechStackFactory::create();
        $repo = \stubDeleteTechStackRepo($techStack);
        $handler = new DeleteTechStackHandler($repo);

        $handler(new DeleteTechStackCommand($techStack->getId()->toRfc4122()));

        expect($repo->deleted)->toBeTrue();
    });

    it('throws not found when tech stack does not exist', function () {
        $handler = new DeleteTechStackHandler(\stubDeleteTechStackRepo(null));
        $handler(new DeleteTechStackCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
