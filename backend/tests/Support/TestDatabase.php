<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

final class TestDatabase
{
    private static ?string $name = null;

    public static function create(): void
    {
        if (self::$name !== null) {
            return;
        }

        $url = $_SERVER['TEST_DATABASE_ADMIN_URL'] ?? $_ENV['TEST_DATABASE_ADMIN_URL']
            ?? getenv('TEST_DATABASE_ADMIN_URL') ?: 'postgresql://app:app@postgres:5432/postgres?serverVersion=16&charset=utf8';
        $params = (new DsnParser(['postgresql' => 'pdo_pgsql', 'postgres' => 'pdo_pgsql']))->parse($url);
        if (($params['driver'] ?? null) !== 'pdo_pgsql') {
            throw new \RuntimeException('Integration tests require PostgreSQL.');
        }

        $params['dbname'] = 'postgres';
        $admin = DriverManager::getConnection($params);
        $name = 'appmanager_test_'.bin2hex(random_bytes(8));
        $admin->executeStatement('CREATE DATABASE '.$admin->quoteIdentifier($name));
        self::$name = $name;

        // Only the randomly named database created by this process may be deleted.
        register_shutdown_function(static function () use ($admin, $name): void {
            $admin->executeStatement('DROP DATABASE '.$admin->quoteIdentifier($name).' WITH (FORCE)');
            $admin->close();
        });

        $testUrl = sprintf(
            'postgresql://%s:%s@%s:%d/%s?%s',
            rawurlencode($params['user'] ?? ''),
            rawurlencode($params['password'] ?? ''),
            $params['host'] ?? 'localhost',
            $params['port'] ?? 5432,
            $name,
            http_build_query(array_intersect_key($params, array_flip(['serverVersion', 'charset', 'sslmode']))),
        );
        $_ENV['TEST_DATABASE_URL'] = $_SERVER['TEST_DATABASE_URL'] = $testUrl;
        putenv('TEST_DATABASE_URL='.$testUrl);
    }

    public static function reset(Connection $connection): void
    {
        if (self::$name === null || $connection->fetchOne('SELECT current_database()') !== self::$name) {
            throw new \RuntimeException('Refusing to reset a database not created by this test process.');
        }

        $connection->executeStatement('DROP SCHEMA public CASCADE');
        $connection->executeStatement('CREATE SCHEMA public');
    }
}
