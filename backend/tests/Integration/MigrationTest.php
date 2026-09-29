<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Support\PostgresTestCase;

final class MigrationTest extends PostgresTestCase
{
    protected function migrateOnSetUp(): bool
    {
        return false;
    }

    public function testMigrationFromEmptyDatabaseIsSynchronizedAndReversible(): void
    {
        self::assertSame([], $this->connection->createSchemaManager()->listTableNames());
        $this->console('doctrine:migrations:migrate');
        $tables = $this->connection->createSchemaManager()->listTableNames();
        sort($tables);
        self::assertSame([
            'application_send_logs', 'doctrine_migration_versions', 'job_applications',
            'mail_template_categories', 'mail_templates', 'mailer_settings', 'messenger_messages',
        ], $tables);
        $this->console('doctrine:schema:validate');
        $this->console('doctrine:migrations:migrate');
        self::assertSame(2, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM doctrine_migration_versions'));

        $this->console('doctrine:migrations:migrate', ['version' => '0']);
        self::assertSame(['doctrine_migration_versions'], $this->connection->createSchemaManager()->listTableNames());
        $this->console('doctrine:migrations:migrate');
        $this->console('doctrine:schema:validate');
        $application = $this->application();
        self::assertSame($application->id()->toRfc4122(), $this->connection->fetchOne('SELECT id FROM job_applications'));
    }
}
