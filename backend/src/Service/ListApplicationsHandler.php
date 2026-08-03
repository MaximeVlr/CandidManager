<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ApplicationListResult;
use App\Query\ListApplicationsQuery;
use App\Repository\JobApplicationRepositoryInterface;

final readonly class ListApplicationsHandler
{
    public function __construct(
        private JobApplicationRepositoryInterface $jobApplications,
    ) {
    }

    public function __invoke(ListApplicationsQuery $query): ApplicationListResult
    {
        $page = max(1, $query->page);
        $limit = min(100, max(1, $query->limit));

        $result = $this->jobApplications->search([
            'search' => $query->search,
            'send_status' => $query->sendStatus,
            'response' => $query->response,
            'follow_up' => $query->followUp,
        ], $query->sort, $query->direction, $page, $limit);

        return new ApplicationListResult($result['items'], $result['total'], $page, $limit);
    }
}
