<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MailTemplate;
use App\Repository\MailTemplateRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MailTemplate>
 */
final class DoctrineMailTemplateRepository extends ServiceEntityRepository implements MailTemplateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailTemplate::class);
    }

    public function save(MailTemplate $mailTemplate): void
    {
        $this->getEntityManager()->persist($mailTemplate);
        $this->getEntityManager()->flush();
    }

    public function findDefault(): ?MailTemplate
    {
        return $this->findOneBy(['name' => 'default']);
    }

    public function findByName(string $name): ?MailTemplate
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function findAllTemplates(): array
    {
        return $this->findBy([], ['name' => 'ASC']);
    }
}
