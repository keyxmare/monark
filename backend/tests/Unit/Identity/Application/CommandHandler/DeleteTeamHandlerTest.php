<?php

declare(strict_types=1);

use App\Identity\Application\Command\DeleteTeamCommand;
use App\Identity\Application\CommandHandler\DeleteTeamHandler;
use App\Identity\Domain\Model\Team;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubDeleteTeamRepo(?Team $team = null): TeamRepositoryInterface
{
    return new class ($team) implements TeamRepositoryInterface {
        public bool $deleted = false;
        public function __construct(private readonly ?Team $team) {}
        public function findById(Uuid $id): ?Team { return $this->team; }
        public function findBySlug(string $slug): ?Team { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(Team $team): void {}
        public function delete(Team $team): void { $this->deleted = true; }
    };
}

describe('DeleteTeamHandler', function () {
    it('deletes a team successfully', function () {
        $team = Team::create(name: 'Engineering', slug: 'engineering');
        $repo = stubDeleteTeamRepo($team);
        $handler = new DeleteTeamHandler($repo);

        $handler(new DeleteTeamCommand($team->getId()->toRfc4122()));

        expect($repo->deleted)->toBeTrue();
    });

    it('throws not found when team does not exist', function () {
        $handler = new DeleteTeamHandler(stubDeleteTeamRepo(null));
        $handler(new DeleteTeamCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
