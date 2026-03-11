<?php

declare(strict_types=1);

use App\Activity\Application\Command\CreateActivityEventCommand;
use App\Activity\Application\CommandHandler\CreateActivityEventHandler;
use App\Activity\Application\DTO\ActivityEventOutput;
use App\Activity\Application\DTO\CreateActivityEventInput;
use App\Activity\Domain\Model\ActivityEvent;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubActivityEventRepo(): ActivityEventRepositoryInterface
{
    return new class implements ActivityEventRepositoryInterface {
        public ?ActivityEvent $saved = null;
        public function findById(Uuid $id): ?ActivityEvent { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(ActivityEvent $event): void { $this->saved = $event; }
    };
}

describe('CreateActivityEventHandler', function () {
    it('creates an activity event successfully', function () {
        $repo = stubActivityEventRepo();
        $handler = new CreateActivityEventHandler($repo);

        $input = new CreateActivityEventInput(
            type: 'project.created',
            entityType: 'Project',
            entityId: 'abc-123',
            payload: ['name' => 'My Project'],
            userId: '00000000-0000-0000-0000-000000000001',
        );

        $result = $handler(new CreateActivityEventCommand($input));

        expect($result)->toBeInstanceOf(ActivityEventOutput::class);
        expect($result->type)->toBe('project.created');
        expect($result->entityType)->toBe('Project');
        expect($result->entityId)->toBe('abc-123');
        expect($result->payload)->toBe(['name' => 'My Project']);
        expect($result->userId)->toBe('00000000-0000-0000-0000-000000000001');
        expect($repo->saved)->not->toBeNull();
    });
});
