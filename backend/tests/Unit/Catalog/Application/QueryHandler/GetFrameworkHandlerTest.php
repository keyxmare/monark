<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\FrameworkOutput;
use App\Catalog\Application\Query\GetFrameworkQuery;
use App\Catalog\Application\QueryHandler\GetFrameworkHandler;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\FrameworkFactory;

function stubGetFrameworkRepo(?Framework $framework = null): FrameworkRepositoryInterface
{
    return new class ($framework) implements FrameworkRepositoryInterface {
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
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

describe('GetFrameworkHandler', function () {
    it('returns a framework output with language info', function () {
        $framework = FrameworkFactory::create(name: 'Symfony', version: '7.1');
        $handler = new GetFrameworkHandler(\stubGetFrameworkRepo($framework));

        $result = $handler(new GetFrameworkQuery($framework->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(FrameworkOutput::class)
            ->and($result->name)->toBe('Symfony')
            ->and($result->version)->toBe('7.1')
            ->and($result->languageName)->toBe('PHP');
    });

    it('throws NotFoundException when framework does not exist', function () {
        $handler = new GetFrameworkHandler(\stubGetFrameworkRepo(null));

        $handler(new GetFrameworkQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
