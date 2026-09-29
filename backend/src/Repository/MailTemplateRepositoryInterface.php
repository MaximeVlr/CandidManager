<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MailTemplate;
use Symfony\Component\Uid\Uuid;

interface MailTemplateRepositoryInterface
{
    public function save(MailTemplate $mailTemplate): void;

    public function delete(MailTemplate $mailTemplate): void;

    public function findDefault(): ?MailTemplate;

    public function findByName(string $name): ?MailTemplate;

    public function findById(Uuid $id): ?MailTemplate;

    /**
     * @return list<MailTemplate>
     */
    public function findAllTemplates(): array;
}
