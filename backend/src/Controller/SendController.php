<?php

declare(strict_types=1);

namespace App\Controller;

use App\Command\Application\SendApplicationCommand;
use App\Service\SendApplicationHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final readonly class SendController
{
    public function __construct(
        private SendApplicationHandler $sendApplication,
    ) {
    }

    #[Route('/api/send/{id}', name: 'api_send_application', methods: ['POST'])]
    public function send(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(['message' => 'Identifiant invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $result = ($this->sendApplication)(new SendApplicationCommand(Uuid::fromString($id)));

        return new JsonResponse(
            $result->toArray(),
            $result->sent ? JsonResponse::HTTP_OK : JsonResponse::HTTP_BAD_REQUEST,
        );
    }

    #[Route('/api/send/{id}/follow-up', name: 'api_send_application_follow_up', methods: ['POST'])]
    public function followUp(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(['message' => 'Identifiant invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $result = ($this->sendApplication)(new SendApplicationCommand(Uuid::fromString($id), countFollowUp: true));

        return new JsonResponse(
            $result->toArray(),
            $result->sent ? JsonResponse::HTTP_OK : JsonResponse::HTTP_BAD_REQUEST,
        );
    }
}
