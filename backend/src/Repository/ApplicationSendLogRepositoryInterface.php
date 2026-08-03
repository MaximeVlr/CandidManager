<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApplicationSendLog;

interface ApplicationSendLogRepositoryInterface
{
    /**
     * @param array{search?: string|null, status?: string|null} $filters
     * @return array{items: list<ApplicationSendLog>, total: int}
     */
    public function search(array $filters, int $page, int $limit): array;
}
