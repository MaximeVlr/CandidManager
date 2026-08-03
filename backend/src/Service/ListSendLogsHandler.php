<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\SendLogListResult;
use App\Query\ListSendLogsQuery;
use App\Repository\ApplicationSendLogRepositoryInterface;

final readonly class ListSendLogsHandler
{
    public function __construct(
        private ApplicationSendLogRepositoryInterface $sendLogs,
    ) {
    }

    public function __invoke(ListSendLogsQuery $query): SendLogListResult
    {
        $page = max(1, $query->page);
        $limit = min(100, max(1, $query->limit));

        $result = $this->sendLogs->search([
            'search' => $query->search,
            'status' => $query->status,
        ], $page, $limit);

        return new SendLogListResult($result['items'], $result['total'], $page, $limit);
    }
}
