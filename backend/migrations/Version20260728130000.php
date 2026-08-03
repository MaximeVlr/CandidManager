<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create mailer settings table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mailer_settings (
            id UUID NOT NULL,
            provider VARCHAR(40) NOT NULL,
            enabled BOOLEAN NOT NULL,
            from_email VARCHAR(180) NOT NULL,
            from_name VARCHAR(120) NOT NULL,
            username VARCHAR(180) NOT NULL,
            encrypted_password TEXT DEFAULT NULL,
            host VARCHAR(180) NOT NULL,
            port INT NOT NULL,
            encryption VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE mailer_settings');
    }
}
