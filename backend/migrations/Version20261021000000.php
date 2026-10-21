<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add an archive flag to job applications, defaulting to false.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_applications ADD archived BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_applications DROP archived');
    }
}
