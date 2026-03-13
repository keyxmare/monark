<?php

declare(strict_types=1);

use App\Identity\Application\Command\CreateTeamCommand;
use App\Identity\Application\CommandHandler\CreateTeamHandler;
use App\Identity\Application\DTO\CreateTeamInput;
use App\Identity\Application\DTO\TeamOutput;
use App\Identity\Domain\Model\Team;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubCreateTeamRepo(?Team $findBySlugResult = null): TeamRepositoryInterface
{
    return new class ($findBySlugResult) implements TeamRepositoryInterface {
        public ?Team $saved = null;
        public function __construct(private readonly ?Team $findBySlugResult)
        {
        }
        public function findById(Uuid $id): ?Team
        {
            return null;
        }
        public function findBySlug(string $slug): ?Team
        {
            return $this->findBySlugResult;
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

describe('CreateTeamHandler', function () {
    it('creates a team successfully', function () {
        $repo = \stubCreateTeamRepo(null);
        $handler = new CreateTeamHandler($repo);

        $input = new CreateTeamInput(
            name: 'Engineering',
            slug: 'engineering',
            description: 'The engineering team',
        );

        $result = $handler(new CreateTeamCommand($input));

        expect($result)->toBeInstanceOf(TeamOutput::class);
        expect($result->name)->toBe('Engineering');
        expect($result->slug)->toBe('engineering');
        expect($result->description)->toBe('The engineering team');
        expect($repo->saved)->not->toBeNull();
    });

    it('throws exception when slug already exists', function () {
        $existingTeam = Team::create(name: 'Engineering', slug: 'engineering');
        $handler = new CreateTeamHandler(\stubCreateTeamRepo($existingTeam));

        $input = new CreateTeamInput(name: 'Engineering', slug: 'engineering');
        $handler(new CreateTeamCommand($input));
    })->throws(\DomainException::class, 'A team with this slug already exists.');
});
