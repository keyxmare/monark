<?php

declare(strict_types=1);

use App\Identity\Application\DTO\TeamOutput;
use App\Identity\Application\Query\GetTeamQuery;
use App\Identity\Application\QueryHandler\GetTeamHandler;
use App\Identity\Domain\Model\Team;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubGetTeamRepo(?Team $team = null): TeamRepositoryInterface
{
    return new class ($team) implements TeamRepositoryInterface {
        public function __construct(private readonly ?Team $team)
        {
        }
        public function findById(Uuid $id): ?Team
        {
            return $this->team;
        }
        public function findBySlug(string $slug): ?Team
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function save(Team $team): void
        {
        }
        public function delete(Team $team): void
        {
        }
    };
}

describe('GetTeamHandler', function () {
    it('returns a team by id', function () {
        $team = Team::create(name: 'Engineering', slug: 'engineering', description: 'The engineering team');
        $handler = new GetTeamHandler(\stubGetTeamRepo($team));
        $result = $handler(new GetTeamQuery($team->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(TeamOutput::class);
        expect($result->name)->toBe('Engineering');
        expect($result->slug)->toBe('engineering');
    });

    it('throws not found when team does not exist', function () {
        $handler = new GetTeamHandler(\stubGetTeamRepo(null));
        $handler(new GetTeamQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
