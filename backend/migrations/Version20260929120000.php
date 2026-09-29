<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Assign an optional mail template to each job application.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_applications ADD template_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_F8AAF3DF5DA0FB8 ON job_applications (template_id)');
        $this->addSql('ALTER TABLE job_applications ADD CONSTRAINT FK_F8AAF3DF5DA0FB8 FOREIGN KEY (template_id) REFERENCES mail_templates (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_applications DROP CONSTRAINT FK_F8AAF3DF5DA0FB8');
        $this->addSql('DROP INDEX IDX_F8AAF3DF5DA0FB8');
        $this->addSql('ALTER TABLE job_applications DROP template_id');
    }
}
