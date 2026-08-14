<?php

declare(strict_types=1);

namespace App\Service;

use App\Query\ExportApplicationsQuery;
use App\Repository\JobApplicationRepositoryInterface;

final readonly class ExportApplicationsHandler
{
    public function __construct(
        private JobApplicationRepositoryInterface $jobApplications,
        private ApplicationCsvExporter $csvExporter,
        private ApplicationJsonExporter $jsonExporter,
    ) {
    }

    public function __invoke(ExportApplicationsQuery $query): string
    {
        return $this->exportCsv($query);
    }

    public function exportCsv(ExportApplicationsQuery $query): string
    {
        return $this->csvExporter->export($this->findItems($query));
    }

    public function exportJson(ExportApplicationsQuery $query): string
    {
        return $this->jsonExporter->export($this->findItems($query));
    }

    /**
     * @return list<\App\Entity\JobApplication>
     */
    private function findItems(ExportApplicationsQuery $query): array
    {
        $limit = min(10000, max(1, $query->limit));

        return $this->jobApplications->findForExport([
            'search' => $query->search,
            'send_status' => $query->sendStatus,
            'response' => $query->response,
            'follow_up_count' => $query->followUpCount,
        ], $query->sort, $query->direction, $limit);
    }
}
