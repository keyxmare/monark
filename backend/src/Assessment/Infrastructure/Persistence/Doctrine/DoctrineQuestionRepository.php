<?php

declare(strict_types=1);

namespace App\Assessment\Infrastructure\Persistence\Doctrine;

use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineQuestionRepository implements QuestionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Question
    {
        return $this->entityManager->getRepository(Question::class)->find($id);
    }

    /** @return list<Question> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        /** @var list<Question> */
        return $this->entityManager->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->orderBy('q.position', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Question> */
    public function findByQuizId(Uuid $quizId, int $page = 1, int $perPage = 20): array
    {
        /** @var list<Question> */
        return $this->entityManager->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->where('q.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->orderBy('q.position', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByQuizId(Uuid $quizId): int
    {
        return (int) $this->entityManager->getRepository(Question::class)
            ->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Question $question): void
    {
        $this->entityManager->persist($question);
        $this->entityManager->flush();
    }

    public function delete(Question $question): void
    {
        $this->entityManager->remove($question);
        $this->entityManager->flush();
    }
}
