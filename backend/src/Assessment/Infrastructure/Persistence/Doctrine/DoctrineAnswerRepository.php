<?php

declare(strict_types=1);

namespace App\Assessment\Infrastructure\Persistence\Doctrine;

use App\Assessment\Domain\Model\Answer;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineAnswerRepository implements AnswerRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Answer
    {
        return $this->entityManager->getRepository(Answer::class)->find($id);
    }

    /** @return list<Answer> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(Answer::class)
            ->createQueryBuilder('a')
            ->orderBy('a.position', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Answer> */
    public function findByQuestionId(Uuid $questionId, int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(Answer::class)
            ->createQueryBuilder('a')
            ->where('a.question = :questionId')
            ->setParameter('questionId', $questionId)
            ->orderBy('a.position', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Answer::class)
            ->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByQuestionId(Uuid $questionId): int
    {
        return (int) $this->entityManager->getRepository(Answer::class)
            ->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.question = :questionId')
            ->setParameter('questionId', $questionId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Answer $answer): void
    {
        $this->entityManager->persist($answer);
        $this->entityManager->flush();
    }

    public function delete(Answer $answer): void
    {
        $this->entityManager->remove($answer);
        $this->entityManager->flush();
    }
}
