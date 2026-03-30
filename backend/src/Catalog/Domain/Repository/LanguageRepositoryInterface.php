<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Language;
use Symfony\Component\Uid\Uuid;

interface LanguageRepositoryInterface
{
    public function findById(Uuid $id): ?Language;

    /** @return list<Language> */
    public function findAll(): array;

    /** @return list<Language> */
    public function findByProjectId(Uuid $projectId): array;

    public function findByNameAndProjectId(string $name, Uuid $projectId): ?Language;

    public function save(Language $language): void;

    public function delete(Language $language): void;

    public function deleteByProjectId(Uuid $projectId): void;
}
