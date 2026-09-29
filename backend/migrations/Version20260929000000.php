<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional categories to mail templates.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mail_template_categories (id UUID NOT NULL, name VARCHAR(120) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EB0D1AF5E237E06 ON mail_template_categories (name)');
        $this->addSql('ALTER TABLE mail_templates ADD category_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_17F263ED12469DE2 ON mail_templates (category_id)');
        $this->addSql('ALTER TABLE mail_templates ADD CONSTRAINT FK_17F263ED12469DE2 FOREIGN KEY (category_id) REFERENCES mail_template_categories (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mail_templates DROP CONSTRAINT FK_17F263ED12469DE2');
        $this->addSql('DROP INDEX IDX_17F263ED12469DE2');
        $this->addSql('ALTER TABLE mail_templates DROP category_id');
        $this->addSql('DROP TABLE mail_template_categories');
    }
}
