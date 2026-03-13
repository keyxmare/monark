<?php

declare(strict_types=1);

use App\Identity\Application\DTO\TeamListOutput;
use App\Identity\Application\Query\ListTeamsQuery;
use App\Identity\Application\QueryHandler\ListTeamsHandler;
use App\Identity\Domain\Model\Team;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListTeamsRepo(array $teams = [], int $count = 0): TeamRepositoryInterface
{
    return new class ($teams, $count) implements TeamRepositoryInterface {
        public function __construct(private readonly array $teams, private readonly int $count)
        {
        }
        public function findById(Uuid $id): ?Team
        {
            return null;
        }
        public function findBySlug(string $slug): ?Team
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return $this->teams;
        }
        public function count(): int
        {
            return $this->count;
        }
        public function save(Team $team): void
        {
        }
        public function delete(Team $team): void
        {
        }
    };
}

describe('ListTeamsHandler', function () {
    it('returns paginated teams', function () {
        $team1 = Team::create(name: 'Engineering', slug: 'engineering');
        $team2 = Team::create(name: 'Design', slug: 'design');

        $handler = new ListTeamsHandler(\stubListTeamsRepo([$team1, $team2], 2));
        $result = $handler(new ListTeamsQuery(1, 20));

        expect($result)->toBeInstanceOf(TeamListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no teams', function () {
        $handler = new ListTeamsHandler(\stubListTeamsRepo([], 0));
        $result = $handler(new ListTeamsQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});
