<?php

declare(strict_types=1);

namespace App\Controller;

use App\Command\Application\UpdateApplicationCommand;
use App\Command\Application\CreateApplicationCommand;
use App\Command\Application\DeleteApplicationCommand;
use App\DTO\ApplicationUpdateRequest;
use App\Service\CreateApplicationHandler;
use App\Service\DeleteApplicationHandler;
use App\Service\GetApplicationHandler;
use App\Service\ListApplicationsHandler;
use App\Service\PreviewApplicationHandler;
use App\Service\UpdateApplicationHandler;
use App\Query\ListApplicationsQuery;
use App\EventSubscriber\Exception\InvalidApplicationsJsonException;
use App\Service\WebUrlValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final readonly class ApplicationController
{
    public function __construct(
        private ListApplicationsHandler $listApplications,
        private CreateApplicationHandler $createApplication,
        private GetApplicationHandler $getApplication,
        private UpdateApplicationHandler $updateApplication,
        private DeleteApplicationHandler $deleteApplication,
        private PreviewApplicationHandler $previewApplication,
        private WebUrlValidator $webUrlValidator,
    ) {
    }

    #[Route('/api/applications', name: 'api_applications_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $result = ($this->listApplications)(new ListApplicationsQuery(
            search: $request->query->get('search'),
            sendStatus: $request->query->get('send_status'),
            response: $request->query->get('response'),
            followUpCount: $this->parseFollowUpCount($request),
            sort: $request->query->get('sort', 'created_at'),
            direction: $request->query->get('direction', 'desc'),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 25),
        ));

        return new JsonResponse($result->toArray());
    }

    #[Route('/api/applications', name: 'api_applications_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $updateRequest = $this->parseApplicationPayload($request);

        if ($updateRequest instanceof JsonResponse) {
            return $updateRequest;
        }

        $jobApplication = ($this->createApplication)(new CreateApplicationCommand($updateRequest));

        if ($jobApplication === null) {
            return new JsonResponse([
                'message' => 'Une candidature existe deja pour cette entreprise et cet email.',
            ], JsonResponse::HTTP_CONFLICT);
        }

        return new JsonResponse($jobApplication->toArray(), JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/applications/{id}', name: 'api_applications_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(['message' => 'Identifiant invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $jobApplication = ($this->getApplication)(Uuid::fromString($id));

        if ($jobApplication === null) {
            return new JsonResponse(['message' => 'Candidature introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($jobApplication->toArray());
    }

    #[Route('/api/applications/{id}/preview', name: 'api_applications_preview', methods: ['GET'])]
    public function preview(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(['message' => 'Identifiant invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $preview = ($this->previewApplication)(Uuid::fromString($id));

        if ($preview === null) {
            return new JsonResponse(['message' => 'Candidature ou template introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($preview->toArray());
    }

    #[Route('/api/applications/{id}', name: 'api_applications_update', methods: ['PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(['message' => 'Identifiant invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $updateRequest = $this->parseApplicationPayload($request);

        if ($updateRequest instanceof JsonResponse) {
            return $updateRequest;
        }

        $jobApplication = ($this->updateApplication)(new UpdateApplicationCommand(Uuid::fromString($id), $updateRequest));

        if ($jobApplication === null) {
            return new JsonResponse(['message' => 'Candidature introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($jobApplication->toArray());
    }

    #[Route('/api/applications/{id}', name: 'api_applications_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(['message' => 'Identifiant invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $deleted = ($this->deleteApplication)(new DeleteApplicationCommand(Uuid::fromString($id)));

        if (!$deleted) {
            return new JsonResponse(['message' => 'Candidature introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    private function parseApplicationPayload(Request $request): ApplicationUpdateRequest|JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return new JsonResponse([
                'message' => 'JSON invalide.',
                'errors' => [['index' => null, 'field' => null, 'message' => $exception->getMessage()]],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!is_array($payload)) {
            return new JsonResponse([
                'message' => 'JSON invalide.',
                'errors' => [['index' => null, 'field' => null, 'message' => 'La racine doit etre un objet.']],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            return ApplicationUpdateRequest::fromArray($payload, $this->webUrlValidator);
        } catch (InvalidApplicationsJsonException $exception) {
            return new JsonResponse([
                'message' => 'La candidature ne respecte pas le format attendu.',
                'errors' => $exception->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
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
