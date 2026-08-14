<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JobApplication;
use App\Entity\Enum\SendStatus;
use App\Repository\JobApplicationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<JobApplication>
 */
final class DoctrineJobApplicationRepository extends ServiceEntityRepository implements JobApplicationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobApplication::class);
    }

    public function save(JobApplication $jobApplication): void
    {
        $this->getEntityManager()->persist($jobApplication);
        $this->getEntityManager()->flush();
    }

    public function delete(JobApplication $jobApplication): void
    {
        $this->getEntityManager()->remove($jobApplication);
        $this->getEntityManager()->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findById(Uuid $id): ?JobApplication
    {
        return $this->find($id);
    }

    public function search(array $filters, string $sort, string $direction, int $page, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('application');

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $queryBuilder
                ->andWhere('LOWER(application.company) LIKE :search OR LOWER(application.location) LIKE :search OR LOWER(application.email) LIKE :search OR LOWER(application.subject) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($filters['search']).'%');
        }

        if (($filters['send_status'] ?? null) !== null && $filters['send_status'] !== '') {
            $queryBuilder
                ->andWhere('application.sendStatus = :sendStatus')
                ->setParameter('sendStatus', $filters['send_status']);
        }

        if (($filters['response'] ?? null) !== null && $filters['response'] !== '') {
            $queryBuilder
                ->andWhere('application.responseStatus = :responseStatus')
                ->setParameter('responseStatus', $filters['response']);
        }

        if (($filters['follow_up_count'] ?? null) !== null) {
            $queryBuilder
                ->andWhere('application.followUpCount = :followUpCount')
                ->setParameter('followUpCount', $filters['follow_up_count']);
        }

        $countQueryBuilder = clone $queryBuilder;
        $total = (int) $countQueryBuilder
            ->select('COUNT(application.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $allowedSorts = [
            'company' => 'application.company',
            'location' => 'application.location',
            'email' => 'application.email',
            'send_status' => 'application.sendStatus',
            'response' => 'application.responseStatus',
            'created_at' => 'application.createdAt',
            'updated_at' => 'application.updatedAt',
        ];

        $sortColumn = $allowedSorts[$sort] ?? 'application.createdAt';
        $sortDirection = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        $items = $queryBuilder
            ->orderBy($sortColumn, $sortDirection)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public function findByIds(array $ids, int $limit): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('application')
            ->andWhere('application.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPendingForSending(int $limit): array
    {
        return $this->createQueryBuilder('application')
            ->andWhere('application.sendStatus = :status')
            ->setParameter('status', SendStatus::Pending)
            ->orderBy('application.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findFailedForRetry(int $limit): array
    {
        return $this->createQueryBuilder('application')
            ->andWhere('application.sendStatus = :status')
            ->setParameter('status', SendStatus::Failed)
            ->orderBy('application.updatedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findForExport(array $filters, string $sort, string $direction, int $limit): array
    {
        $result = $this->search($filters, $sort, $direction, 1, $limit);

        return $result['items'];
    }
}
