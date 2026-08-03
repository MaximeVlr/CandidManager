<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create application, send log, and mail template tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE job_applications (
            id UUID NOT NULL,
            company VARCHAR(180) NOT NULL,
            location VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL,
            subject VARCHAR(180) NOT NULL,
            custom_message TEXT NOT NULL,
            official_source_url TEXT NOT NULL,
            send_status VARCHAR(255) NOT NULL,
            response_status VARCHAR(255) NOT NULL,
            follow_up BOOLEAN NOT NULL,
            sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            last_error TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_job_applications_email_company ON job_applications (email, company)');

        $this->addSql('CREATE TABLE application_send_logs (
            id UUID NOT NULL,
            job_application_id UUID NOT NULL,
            status VARCHAR(255) NOT NULL,
            smtp_code VARCHAR(80) DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            duration_ms INT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_application_send_logs_job_application_id ON application_send_logs (job_application_id)');
        $this->addSql('ALTER TABLE application_send_logs ADD CONSTRAINT fk_application_send_logs_job_application_id FOREIGN KEY (job_application_id) REFERENCES job_applications (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE mail_templates (
            id UUID NOT NULL,
            name VARCHAR(120) NOT NULL,
            html_body TEXT NOT NULL,
            text_body TEXT NOT NULL,
            cv_original_name VARCHAR(255) DEFAULT NULL,
            cv_stored_name VARCHAR(255) DEFAULT NULL,
            cv_mime_type VARCHAR(120) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_mail_templates_name ON mail_templates (name)');

        $this->addSql('CREATE TABLE messenger_messages (
            id BIGSERIAL NOT NULL,
            body TEXT NOT NULL,
            headers TEXT NOT NULL,
            queue_name VARCHAR(190) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_messenger_messages_queue_name ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX idx_messenger_messages_available_at ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX idx_messenger_messages_delivered_at ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application_send_logs DROP CONSTRAINT fk_application_send_logs_job_application_id');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE application_send_logs');
        $this->addSql('DROP TABLE mail_templates');
        $this->addSql('DROP TABLE job_applications');
    }
}
