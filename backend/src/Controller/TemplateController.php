<?php

declare(strict_types=1);

namespace App\Controller;

use App\Command\Application\UpdateTemplateCommand;
use App\DTO\TemplateUpdateRequest;
use App\Entity\MailTemplate;
use App\Entity\MailTemplateCategory;
use App\Repository\DoctrineMailTemplateCategoryRepository;
use App\Repository\MailTemplateRepositoryInterface;
use App\Service\AttachCvToTemplateHandler;
use App\Service\CvAttachmentStorage;
use App\Service\ListTemplatesHandler;
use App\Service\MailTemplateValidator;
use App\Service\UpdateTemplateHandler;
use App\EventSubscriber\Exception\InvalidApplicationsJsonException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final readonly class TemplateController
{
    public function __construct(
        private ListTemplatesHandler $listTemplates,
        private UpdateTemplateHandler $updateTemplate,
        private AttachCvToTemplateHandler $attachCvToTemplate,
        private CvAttachmentStorage $cvStorage,
        private MailTemplateRepositoryInterface $mailTemplates,
        private DoctrineMailTemplateCategoryRepository $categories,
        private MailTemplateValidator $validator,
    ) {
    }

    #[Route('/api/templates', name: 'api_templates_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(($this->listTemplates)());
    }

    #[Route('/api/templates', name: 'api_templates_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = $this->parsePayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $name = $payload['name'] ?? null;
        if (!is_string($name) || $name !== trim($name) || $name === '' || mb_strlen($name) > 120 || str_contains($name, '/')) {
            return new JsonResponse(['message' => 'Le nom du template doit contenir entre 1 et 120 caractères, sans barre oblique.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->mailTemplates->findByName($name) !== null) {
            return new JsonResponse(['message' => 'Un template porte déjà ce nom.'], JsonResponse::HTTP_CONFLICT);
        }

        $templateRequest = $this->parseTemplateRequest($payload);
        if ($templateRequest instanceof JsonResponse) {
            return $templateRequest;
        }

        $category = $this->resolveCategory($templateRequest);
        if ($category instanceof JsonResponse) {
            return $category;
        }

        try {
            $this->validator->validate($templateRequest->htmlBody, $templateRequest->textBody);
            $template = new MailTemplate($name, $templateRequest->htmlBody, $templateRequest->textBody);
            if ($templateRequest->categoryProvided) {
                $template->setCategory($category);
            }
            $this->mailTemplates->save($template);
        } catch (InvalidApplicationsJsonException $exception) {
            return $this->invalidTemplate($exception);
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse(['message' => 'Un template porte déjà ce nom.'], JsonResponse::HTTP_CONFLICT);
        }

        return new JsonResponse($template->toArray(), JsonResponse::HTTP_CREATED, [
            'Location' => '/api/templates/'.rawurlencode($name),
        ]);
    }

    #[Route('/api/templates/{name}', name: 'api_templates_get', methods: ['GET'])]
    public function get(string $name): JsonResponse
    {
        $template = $this->mailTemplates->findByName($name);

        return $template === null
            ? new JsonResponse(['message' => 'Template introuvable.'], JsonResponse::HTTP_NOT_FOUND)
            : new JsonResponse($template->toArray());
    }

    #[Route('/api/templates/{name}', name: 'api_templates_update', methods: ['PUT'])]
    public function update(string $name, Request $request): JsonResponse
    {
        if ($name !== trim($name) || $name === '' || mb_strlen($name) > 120) {
            return new JsonResponse(['message' => 'Nom de template invalide.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = $this->parsePayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $templateRequest = $this->parseTemplateRequest($payload);
        if ($templateRequest instanceof JsonResponse) {
            return $templateRequest;
        }

        $category = $this->resolveCategory($templateRequest);
        if ($category instanceof JsonResponse) {
            return $category;
        }

        try {
            $template = ($this->updateTemplate)(new UpdateTemplateCommand($name, $templateRequest, $category));
        } catch (InvalidApplicationsJsonException $exception) {
            return $this->invalidTemplate($exception);
        }

        return new JsonResponse($template->toArray());
    }

    #[Route('/api/templates/{name}', name: 'api_templates_delete', methods: ['DELETE'])]
    public function delete(string $name): JsonResponse
    {
        $template = $this->mailTemplates->findByName($name);
        if ($template === null) {
            return new JsonResponse(['message' => 'Template introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->mailTemplates->delete($template);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/templates/{name}/cv', name: 'api_templates_attach_cv', methods: ['POST'])]
    public function attachCv(string $name, Request $request): JsonResponse
    {
        if ($this->mailTemplates->findByName($name) === null) {
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

    #[Route('/api/templates/{name}/cv/preview', name: 'api_templates_cv_preview', methods: ['GET'])]
    public function previewCv(string $name): BinaryFileResponse|JsonResponse
    {
        $template = $this->mailTemplates->findByName($name);
        if ($template === null) {
            return new JsonResponse(['message' => 'Template introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $storedName = $template->cvStoredName();
        $originalName = $template->cvOriginalName();
        if ($storedName === null || $originalName === null || str_contains($storedName, '/') || str_contains($storedName, '\\')) {
            return new JsonResponse(['message' => 'Aucun CV associé à ce template.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $path = $this->cvStorage->absolutePath($storedName);
        if (!is_file($path) || !is_readable($path)) {
            return new JsonResponse(['message' => 'Fichier CV introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $template->cvMimeType() ?? 'application/octet-stream');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $originalName);

        return $response;
    }

    /** @return array<string, mixed>|JsonResponse */
    private function parsePayload(Request $request): array|JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return new JsonResponse(['message' => 'JSON invalide.', 'errors' => [[
                'index' => null, 'field' => null, 'message' => $exception->getMessage(),
            ]]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!is_object($payload)) {
            return new JsonResponse(['message' => 'JSON invalide.', 'errors' => [[
                'index' => null, 'field' => null, 'message' => 'La racine doit etre un objet.',
            ]]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return (array) $payload;
    }

    /** @param array<string, mixed> $payload */
    private function parseTemplateRequest(array $payload): TemplateUpdateRequest|JsonResponse
    {
        try {
            return TemplateUpdateRequest::fromArray($payload);
        } catch (InvalidApplicationsJsonException $exception) {
            return $this->invalidTemplate($exception);
        }
    }

    private function resolveCategory(TemplateUpdateRequest $request): MailTemplateCategory|JsonResponse|null
    {
        if ($request->categoryId === null) {
            return null;
        }

        return $this->categories->find($request->categoryId)
            ?? new JsonResponse(['message' => 'Catégorie introuvable.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function invalidTemplate(InvalidApplicationsJsonException $exception): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Le template ne respecte pas le format attendu.',
            'errors' => $exception->errors(),
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
