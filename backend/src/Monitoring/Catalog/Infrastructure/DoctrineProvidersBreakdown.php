<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Infrastructure;

use App\Hub\Shared\Domain\Port\ProvidersBreakdownInterface;
use App\Monitoring\Catalog\Domain\Model\ProviderType;
use Doctrine\DBAL\Connection;

final readonly class DoctrineProvidersBreakdown implements ProvidersBreakdownInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function byType(): array
    {
        $sql = <<<'SQL'
            SELECT
                pr.type AS type,
                COUNT(p.id) AS projects_count,
                BOOL_OR(pr.status = 'connected') AS connected
            FROM catalog_providers pr
            LEFT JOIN catalog_projects p ON p.provider_id = pr.id
            GROUP BY pr.type
            ORDER BY projects_count DESC, pr.type ASC
            SQL;

        /** @var list<array{type: string, projects_count: int|string, connected: bool|string}> $rows */
        $rows = $this->connection->fetchAllAssociative($sql);

        $result = [];
        foreach ($rows as $row) {
            $type = ProviderType::tryFrom((string) $row['type']);
            if ($type === null) {
                continue;
            }
            $result[] = [
                'type' => $type->value,
                'label' => self::labelFor($type),
                'connected' => (bool) $row['connected'],
                'projects_count' => (int) $row['projects_count'],
            ];
        }

        return $result;
    }

    private static function labelFor(ProviderType $type): string
    {
        return match ($type) {
            ProviderType::GitHub => 'GitHub',
            ProviderType::GitLab => 'GitLab',
            ProviderType::Bitbucket => 'Bitbucket',
        };
    }
}
