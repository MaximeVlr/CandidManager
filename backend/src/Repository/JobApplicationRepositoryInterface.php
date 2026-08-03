<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\JobApplication;
use Symfony\Component\Uid\Uuid;

interface JobApplicationRepositoryInterface
{
    public function save(JobApplication $jobApplication): void;

    public function delete(JobApplication $jobApplication): void;

    public function flush(): void;

    public function findById(Uuid $id): ?JobApplication;

    /**
     * @param array{search?: string|null, send_status?: string|null, response?: string|null, follow_up?: bool|null} $filters
     * @return array{items: list<JobApplication>, total: int}
     */
    public function search(array $filters, string $sort, string $direction, int $page, int $limit): array;

    /**
     * @param list<string> $ids
     * @return list<JobApplication>
     */
    public function findByIds(array $ids, int $limit): array;

    /**
     * @return list<JobApplication>
     */
    public function findPendingForSending(int $limit): array;

    /**
     * @return list<JobApplication>
     */
    public function findFailedForRetry(int $limit): array;

    /**
     * @param array{search?: string|null, send_status?: string|null, response?: string|null, follow_up?: bool|null} $filters
     * @return list<JobApplication>
     */
    public function findForExport(array $filters, string $sort, string $direction, int $limit): array;
}
