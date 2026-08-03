<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add CV attachment metadata to mail templates.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mail_templates ADD cv_original_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mail_templates ADD cv_stored_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mail_templates ADD cv_mime_type VARCHAR(120) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mail_templates DROP cv_original_name');
        $this->addSql('ALTER TABLE mail_templates DROP cv_stored_name');
        $this->addSql('ALTER TABLE mail_templates DROP cv_mime_type');
    }
}
