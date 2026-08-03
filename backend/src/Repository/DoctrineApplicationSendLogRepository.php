<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApplicationSendLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicationSendLog>
 */
final class DoctrineApplicationSendLogRepository extends ServiceEntityRepository implements ApplicationSendLogRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationSendLog::class);
    }

    public function search(array $filters, int $page, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('log')
            ->join('log.jobApplication', 'application')
            ->addSelect('application');

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $queryBuilder
                ->andWhere('LOWER(application.company) LIKE :search OR LOWER(application.email) LIKE :search OR LOWER(log.errorMessage) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($filters['search']).'%');
        }

        if (($filters['status'] ?? null) !== null && $filters['status'] !== '') {
            $queryBuilder
                ->andWhere('log.status = :status')
                ->setParameter('status', $filters['status']);
        }

        $countQueryBuilder = clone $queryBuilder;
        $total = (int) $countQueryBuilder
            ->select('COUNT(log.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $queryBuilder
            ->orderBy('log.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
