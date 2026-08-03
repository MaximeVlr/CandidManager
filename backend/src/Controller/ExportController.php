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
        $followUp = $request->query->get('follow_up');

        return new ExportApplicationsQuery(
            search: $request->query->get('search'),
            sendStatus: $request->query->get('send_status'),
            response: $request->query->get('response'),
            followUp: $followUp === null || $followUp === '' ? null : filter_var($followUp, FILTER_VALIDATE_BOOLEAN),
            sort: $request->query->get('sort', 'created_at'),
            direction: $request->query->get('direction', 'desc'),
            limit: $request->query->getInt('limit', 10000),
        );
    }
}
