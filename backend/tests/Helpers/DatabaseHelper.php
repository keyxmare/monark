<?php

declare(strict_types=1);

namespace App\Tests\Helpers;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

trait DatabaseHelper
{
    private static bool $schemaCreated = false;

    protected function resetDatabase(): void
    {
        if ($_SERVER['APP_ENV'] !== 'test' && $_ENV['APP_ENV'] !== 'test') {
            throw new \RuntimeException('resetDatabase() must only run in the test environment. Set APP_ENV=test.');
        }

        $em = $this->getEntityManager();
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);

        if (! self::$schemaCreated) {
            $schemaTool->dropSchema($metadata);
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
