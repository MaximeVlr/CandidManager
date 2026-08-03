<?php

declare(strict_types=1);

namespace App\Controller;

use App\Command\Application\RetryFailedApplicationsCommand;
use App\Command\Application\SendAllApplicationsCommand;
use App\Command\Application\SendBulkApplicationsCommand;
use App\Service\SendBulkApplicationsHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final readonly class SendBulkController
{
    public function __construct(
        private SendBulkApplicationsHandler $sendBulkApplications,
    ) {
    }

    #[Route('/api/send/bulk', name: 'api_send_bulk', methods: ['POST'])]
    public function bulk(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $ids = is_array($payload) && is_array($payload['ids'] ?? null) ? $payload['ids'] : [];
        $validIds = array_values(array_filter($ids, static fn ($id): bool => is_string($id) && Uuid::isValid($id)));

        $result = $this->sendBulkApplications->selected(new SendBulkApplicationsCommand($validIds));

        return new JsonResponse($result->toArray(), JsonResponse::HTTP_ACCEPTED);
    }

    #[Route('/api/send/all', name: 'api_send_all', methods: ['POST'])]
    public function all(): JsonResponse
    {
        $result = $this->sendBulkApplications->all(new SendAllApplicationsCommand());

        return new JsonResponse($result->toArray(), JsonResponse::HTTP_ACCEPTED);
    }

    #[Route('/api/send/retry-failed', name: 'api_send_retry_failed', methods: ['POST'])]
    public function retryFailed(): JsonResponse
    {
        $result = $this->sendBulkApplications->retryFailed(new RetryFailedApplicationsCommand());

        return new JsonResponse($result->toArray(), JsonResponse::HTTP_ACCEPTED);
    }
}
