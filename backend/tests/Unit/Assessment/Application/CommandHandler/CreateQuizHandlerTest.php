<?php

declare(strict_types=1);

use App\Assessment\Application\Command\CreateQuizCommand;
use App\Assessment\Application\CommandHandler\CreateQuizHandler;
use App\Assessment\Application\DTO\CreateQuizInput;
use App\Assessment\Application\DTO\QuizOutput;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubCreateQuizRepo(?Quiz $findBySlugResult = null): QuizRepositoryInterface
{
    return new class ($findBySlugResult) implements QuizRepositoryInterface {
        public ?Quiz $saved = null;
        public function __construct(private readonly ?Quiz $findBySlugResult)
        {
        }
        public function findById(Uuid $id): ?Quiz
        {
            return null;
        }
        public function findBySlug(string $slug): ?Quiz
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
        public function save(Quiz $quiz): void
        {
            $this->saved = $quiz;
        }
        public function delete(Quiz $quiz): void
        {
        }
    };
}

describe('CreateQuizHandler', function () {
    it('creates a quiz successfully', function () {
        $repo = \stubCreateQuizRepo(null);
        $handler = new CreateQuizHandler($repo);

        $input = new CreateQuizInput(
            title: 'PHP Fundamentals',
            slug: 'php-fundamentals',
            description: 'A quiz about PHP basics',
            type: 'quiz',
            status: 'draft',
        );

        $result = $handler(new CreateQuizCommand($input));

        expect($result)->toBeInstanceOf(QuizOutput::class);
        expect($result->title)->toBe('PHP Fundamentals');
        expect($result->slug)->toBe('php-fundamentals');
        expect($result->type)->toBe('quiz');
        expect($result->status)->toBe('draft');
        expect($repo->saved)->not->toBeNull();
    });

    it('throws exception when slug already exists', function () {
        $existing = Quiz::create(
            title: 'PHP Fundamentals',
            slug: 'php-fundamentals',
            description: 'Existing quiz',
            type: \App\Assessment\Domain\Model\QuizType::Quiz,
        );
        $handler = new CreateQuizHandler(\stubCreateQuizRepo($existing));

        $input = new CreateQuizInput(
            title: 'PHP Fundamentals',
            slug: 'php-fundamentals',
            description: 'Another quiz',
            type: 'quiz',
        );
        $handler(new CreateQuizCommand($input));
    })->throws(\DomainException::class, 'A quiz with this slug already exists.');
});
