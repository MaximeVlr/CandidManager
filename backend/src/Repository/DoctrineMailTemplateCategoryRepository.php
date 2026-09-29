<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MailTemplateCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<MailTemplateCategory> */
final class DoctrineMailTemplateCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailTemplateCategory::class);
    }

    /** @return list<MailTemplateCategory> */
    public function findAllByName(): array
    {
        return $this->findBy([], ['name' => 'ASC']);
    }

    public function save(MailTemplateCategory $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }

    public function delete(MailTemplateCategory $category): void
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }
}
