<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MailTemplate;

interface MailTemplateRepositoryInterface
{
    public function save(MailTemplate $mailTemplate): void;

    public function findDefault(): ?MailTemplate;

    public function findByName(string $name): ?MailTemplate;

    /**
     * @return list<MailTemplate>
     */
    public function findAllTemplates(): array;
}
