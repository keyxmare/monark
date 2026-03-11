<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Repository;

use App\Assessment\Domain\Model\Question;
use Symfony\Component\Uid\Uuid;

interface QuestionRepositoryInterface
{
    public function findById(Uuid $id): ?Question;

    /** @return list<Question> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    /** @return list<Question> */
    public function findByQuizId(Uuid $quizId, int $page = 1, int $perPage = 20): array;

    public function count(): int;

    public function countByQuizId(Uuid $quizId): int;

    public function save(Question $question): void;

    public function delete(Question $question): void;
}
