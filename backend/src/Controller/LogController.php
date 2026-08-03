<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ListSendLogsHandler;
use App\Query\ListSendLogsQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class LogController
{
    public function __construct(
        private ListSendLogsHandler $listSendLogs,
    ) {
    }

    #[Route('/api/logs', name: 'api_logs_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $result = ($this->listSendLogs)(new ListSendLogsQuery(
            search: $request->query->get('search'),
            status: $request->query->get('status'),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 25),
        ));

        return new JsonResponse($result->toArray());
    }
}
