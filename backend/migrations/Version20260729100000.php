<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260729100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add follow-up counter to job applications.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_applications ADD follow_up_count INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_applications DROP follow_up_count');
    }
}
