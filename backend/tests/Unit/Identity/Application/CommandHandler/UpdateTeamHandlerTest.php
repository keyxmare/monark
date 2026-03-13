<?php

declare(strict_types=1);

use App\Identity\Application\Command\UpdateTeamCommand;
use App\Identity\Application\CommandHandler\UpdateTeamHandler;
use App\Identity\Application\DTO\TeamOutput;
use App\Identity\Application\DTO\UpdateTeamInput;
use App\Identity\Domain\Model\Team;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubUpdateTeamRepo(?Team $team = null): TeamRepositoryInterface
{
    return new class ($team) implements TeamRepositoryInterface {
        public ?Team $saved = null;
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
            $this->saved = $team;
        }
        public function delete(Team $team): void
        {
        }
    };
}

describe('UpdateTeamHandler', function () {
    it('updates a team successfully', function () {
        $team = Team::create(name: 'Engineering', slug: 'engineering');
        $teamId = $team->getId()->toRfc4122();

        $repo = \stubUpdateTeamRepo($team);
        $handler = new UpdateTeamHandler($repo);

        $input = new UpdateTeamInput(name: 'Backend Engineering', description: 'Backend team');
        $result = $handler(new UpdateTeamCommand($teamId, $input));

        expect($result)->toBeInstanceOf(TeamOutput::class);
        expect($result->name)->toBe('Backend Engineering');
        expect($result->description)->toBe('Backend team');
        expect($repo->saved)->not->toBeNull();
    });

    it('throws not found when team does not exist', function () {
        $handler = new UpdateTeamHandler(\stubUpdateTeamRepo(null));
        $input = new UpdateTeamInput(name: 'New Name');
        $handler(new UpdateTeamCommand('00000000-0000-0000-0000-000000000000', $input));
    })->throws(NotFoundException::class);
});
