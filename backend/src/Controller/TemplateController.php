<?php

declare(strict_types=1);

namespace App\Controller;

use App\Command\Application\UpdateTemplateCommand;
use App\DTO\TemplateUpdateRequest;
use App\Service\AttachCvToTemplateHandler;
use App\Service\ListTemplatesHandler;
use App\Service\UpdateTemplateHandler;
use App\EventSubscriber\Exception\InvalidApplicationsJsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class TemplateController
{
    private const ALLOWED_TEMPLATE_NAMES = ['default', 'follow_up'];

    public function __construct(
        private ListTemplatesHandler $listTemplates,
        private UpdateTemplateHandler $updateTemplate,
        private AttachCvToTemplateHandler $attachCvToTemplate,
    ) {
    }

    #[Route('/api/templates', name: 'api_templates_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(($this->listTemplates)());
    }

    #[Route('/api/templates/{name}', name: 'api_templates_update', methods: ['PUT'])]
    public function update(string $name, Request $request): JsonResponse
    {
        if (!in_array($name, self::ALLOWED_TEMPLATE_NAMES, true)) {
            return new JsonResponse(['message' => 'Template introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

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
            $updateRequest = TemplateUpdateRequest::fromArray($payload);
            $template = ($this->updateTemplate)(new UpdateTemplateCommand($name, $updateRequest));
        } catch (InvalidApplicationsJsonException $exception) {
            return new JsonResponse([
                'message' => 'Le template ne respecte pas le format attendu.',
                'errors' => $exception->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($template->toArray());
    }

    #[Route('/api/templates/{name}/cv', name: 'api_templates_attach_cv', methods: ['POST'])]
    public function attachCv(string $name, Request $request): JsonResponse
    {
        if (!in_array($name, self::ALLOWED_TEMPLATE_NAMES, true)) {
            return new JsonResponse(['message' => 'Template introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $file = $request->files->get('cv');

        if ($file === null) {
            return new JsonResponse(['message' => 'Le fichier CV est obligatoire.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $template = ($this->attachCvToTemplate)($name, $file);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($template->toArray());
    }
}
