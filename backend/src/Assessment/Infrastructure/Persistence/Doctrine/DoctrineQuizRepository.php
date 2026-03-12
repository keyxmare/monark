<?php

declare(strict_types=1);

namespace App\Assessment\Infrastructure\Persistence\Doctrine;

use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineQuizRepository implements QuizRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Quiz
    {
        return $this->entityManager->getRepository(Quiz::class)->find($id);
    }

    public function findBySlug(string $slug): ?Quiz
    {
        return $this->entityManager->getRepository(Quiz::class)->findOneBy(['slug' => $slug]);
    }

    /** @return list<Quiz> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        /** @var list<Quiz> */
        return $this->entityManager->getRepository(Quiz::class)
            ->createQueryBuilder('q')
            ->orderBy('q.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Quiz::class)
            ->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Quiz $quiz): void
    {
        $this->entityManager->persist($quiz);
        $this->entityManager->flush();
    }

    public function delete(Quiz $quiz): void
    {
        $this->entityManager->remove($quiz);
        $this->entityManager->flush();
    }
}
