<?php

declare(strict_types=1);

namespace App\Tests\Helpers;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use RuntimeException;

trait DatabaseHelper
{
    private static bool $schemaCreated = false;

    protected function resetDatabase(): void
    {
        $em = $this->getEntityManager();
        $dbName = $em->getConnection()->getDatabase();

        if (! \str_ends_with($dbName, '_test')) {
            throw new RuntimeException(
                "resetDatabase() refused: connected to '{$dbName}' which does not end with '_test'. "
                . 'Run tests with APP_ENV=test (e.g. docker compose exec -e APP_ENV=test backend vendor/bin/pest).'
            );
        }

        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);

        if (! self::$schemaCreated) {
            $connection = $em->getConnection();
            $connection->executeStatement('DROP SCHEMA public CASCADE');
            $connection->executeStatement('CREATE SCHEMA public');
            $schemaTool->createSchema($metadata);
            self::$schemaCreated = true;
        } else {
            $connection = $em->getConnection();
            $connection->executeStatement('SET session_replication_role = replica');

            foreach ($metadata as $classMetadata) {
                $connection->executeStatement(
                    'TRUNCATE TABLE ' . $classMetadata->getTableName() . ' CASCADE'
                );
            }

            $connection->executeStatement('SET session_replication_role = DEFAULT');
        }

        $em->clear();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
