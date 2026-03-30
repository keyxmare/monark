<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineLanguageRepository implements LanguageRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Language
    {
        return $this->entityManager->getRepository(Language::class)->find($id);
    }

    /** @return list<Language> */
    public function findAll(): array
    {
        /** @var list<Language> */
        return $this->entityManager->getRepository(Language::class)
            ->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Language> */
    public function findByProjectId(Uuid $projectId): array
    {
        /** @var list<Language> */
        return $this->entityManager->getRepository(Language::class)
            ->createQueryBuilder('l')
            ->where('l.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByNameAndProjectId(string $name, Uuid $projectId): ?Language
    {
        /** @var ?Language */
        return $this->entityManager->getRepository(Language::class)
            ->createQueryBuilder('l')
            ->where('l.name = :name')
            ->andWhere('l.project = :projectId')
            ->setParameter('name', $name)
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Language $language): void
    {
        $this->entityManager->persist($language);
        $this->entityManager->flush();
    }

    public function delete(Language $language): void
    {
        $this->entityManager->remove($language);
        $this->entityManager->flush();
    }

    public function deleteByProjectId(Uuid $projectId): void
    {
        $this->entityManager->getRepository(Language::class)
            ->createQueryBuilder('l')
            ->delete()
            ->where('l.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->execute();
    }
}
