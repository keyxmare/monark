<?php

declare(strict_types=1);

use App\Catalog\Application\Command\DeleteFrameworkCommand;
use App\Catalog\Application\CommandHandler\DeleteFrameworkHandler;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\FrameworkFactory;

function stubDeleteFrameworkRepo(?Framework $framework = null): FrameworkRepositoryInterface
{
    return new class ($framework) implements FrameworkRepositoryInterface {
        public bool $deleted = false;

        public function __construct(private readonly ?Framework $framework)
        {
        }

        public function findById(Uuid $id): ?Framework
        {
            return $this->framework;
        }
        public function findAll(): array
        {
            return [];
        }
        public function findByProjectId(Uuid $projectId): array
        {
            return [];
        }
        public function findByLanguageId(Uuid $languageId): array
        {
            return [];
        }
        public function findByNameAndProjectId(string $name, Uuid $projectId): ?Framework
        {
            return null;
        }
        public function findByName(string $name): array
        {
            return [];
        }
        public function save(Framework $framework): void
        {
        }
        public function delete(Framework $framework): void
        {
            $this->deleted = true;
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

describe('DeleteFrameworkHandler', function () {
    it('deletes a framework successfully', function () {
        $framework = FrameworkFactory::create();
        $repo = \stubDeleteFrameworkRepo($framework);

        $handler = new DeleteFrameworkHandler($repo);
        $handler(new DeleteFrameworkCommand($framework->getId()->toRfc4122()));

        expect($repo->deleted)->toBeTrue();
    });

    it('throws NotFoundException when framework does not exist', function () {
        $repo = \stubDeleteFrameworkRepo(null);
        $handler = new DeleteFrameworkHandler($repo);

        $handler(new DeleteFrameworkCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
