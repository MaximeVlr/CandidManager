<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ExportApplicationsHandler;
use App\Query\ExportApplicationsQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ExportController
{
    public function __construct(
        private ExportApplicationsHandler $exportApplications,
    ) {
    }

    #[Route('/api/export.csv', name: 'api_export_applications_csv', methods: ['GET'])]
    public function csv(Request $request): Response
    {
        $csv = $this->exportApplications->exportCsv($this->buildQuery($request));

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="candidatures.csv"',
        ]);
    }

    #[Route('/api/export.json', name: 'api_export_applications_json', methods: ['GET'])]
    public function json(Request $request): Response
    {
        $json = $this->exportApplications->exportJson($this->buildQuery($request));

        return new Response($json, Response::HTTP_OK, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="candidatures.json"',
        ]);
    }

    private function buildQuery(Request $request): ExportApplicationsQuery
    {
        return new ExportApplicationsQuery(
            search: $request->query->get('search'),
            sendStatus: $request->query->get('send_status'),
            response: $request->query->get('response'),
            followUpCount: $this->parseFollowUpCount($request),
            sort: $request->query->get('sort', 'created_at'),
            direction: $request->query->get('direction', 'desc'),
            limit: $request->query->getInt('limit', 10000),
        );
    }

    private function parseFollowUpCount(Request $request): ?int
    {
        $value = $request->query->get('follow_up_count');

        if ($value === null || $value === '') {
            return null;
        }

        $count = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 5]]);

        return $count === false ? null : $count;
    }
}
