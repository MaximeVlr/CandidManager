<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;

final readonly class ApplicationStatsCalculator
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return array{
     *   total: int,
     *   pending: int,
     *   sending: int,
     *   sent: int,
     *   failed: int,
     *   follow_up: int,
     *   responses: array{none: int, pending: int, positive: int, negative: int},
     *   logs: array{success: int, failure: int},
     *   failure_rate: float
     * }
     */
    public function calculate(): array
    {
        $sendStatuses = $this->countByColumn('job_applications', 'send_status');
        $responses = $this->countByColumn('job_applications', 'response_status');
        $logs = $this->countByColumn('application_send_logs', 'status');
        $total = array_sum($sendStatuses);
        $failed = $sendStatuses['failed'] ?? 0;

        return [
            'total' => $total,
            'pending' => $sendStatuses['pending'] ?? 0,
            'sending' => $sendStatuses['sending'] ?? 0,
            'sent' => $sendStatuses['sent'] ?? 0,
            'failed' => $failed,
            'follow_up' => (int) $this->connection->fetchOne('SELECT COUNT(*) FROM job_applications WHERE follow_up = true'),
            'responses' => [
                'none' => $responses['none'] ?? 0,
                'pending' => $responses['pending'] ?? 0,
                'positive' => $responses['positive'] ?? 0,
                'negative' => $responses['negative'] ?? 0,
            ],
            'logs' => [
                'success' => $logs['success'] ?? 0,
                'failure' => $logs['failure'] ?? 0,
            ],
            'failure_rate' => $total === 0 ? 0.0 : round(($failed / $total) * 100, 2),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function countByColumn(string $table, string $column): array
    {
        $rows = $this->connection->fetchAllAssociative(sprintf(
            'SELECT %s AS label, COUNT(*) AS total FROM %s GROUP BY %s',
            $column,
            $table,
            $column,
        ));

        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row['label']] = (int) $row['total'];
        }

        return $counts;
    }
}
