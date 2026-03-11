<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Repository;

use App\Assessment\Domain\Model\Quiz;
use Symfony\Component\Uid\Uuid;

interface QuizRepositoryInterface
{
    public function findById(Uuid $id): ?Quiz;

    public function findBySlug(string $slug): ?Quiz;

    /** @return list<Quiz> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    public function count(): int;

    public function save(Quiz $quiz): void;

    public function delete(Quiz $quiz): void;
}
