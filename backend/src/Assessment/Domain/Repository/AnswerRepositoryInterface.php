<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Repository;

use App\Assessment\Domain\Model\Answer;
use Symfony\Component\Uid\Uuid;

interface AnswerRepositoryInterface
{
    public function findById(Uuid $id): ?Answer;

    /** @return list<Answer> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    /** @return list<Answer> */
    public function findByQuestionId(Uuid $questionId, int $page = 1, int $perPage = 20): array;

    public function count(): int;

    public function countByQuestionId(Uuid $questionId): int;

    public function save(Answer $answer): void;

    public function delete(Answer $answer): void;
}
