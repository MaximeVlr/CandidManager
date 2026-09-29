<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\MailTemplateCategory;
use App\Repository\DoctrineMailTemplateCategoryRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final readonly class MailTemplateCategoryController
{
    public function __construct(private DoctrineMailTemplateCategoryRepository $categories)
    {
    }

    #[Route('/api/template-categories', name: 'api_template_categories_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(['items' => array_map(
            static fn (MailTemplateCategory $category): array => $category->toArray(),
            $this->categories->findAllByName(),
        )]);
    }

    #[Route('/api/template-categories', name: 'api_template_categories_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $name = $this->parseName($request);
        if ($name instanceof JsonResponse) {
            return $name;
        }

        if ($this->categories->findOneBy(['name' => $name]) !== null) {
            return $this->duplicateName();
        }

        $category = new MailTemplateCategory($name);
        try {
            $this->categories->save($category);
        } catch (UniqueConstraintViolationException) {
            return $this->duplicateName();
        }

        return new JsonResponse($category->toArray(), JsonResponse::HTTP_CREATED, [
            'Location' => '/api/template-categories/'.$category->id()->toRfc4122(),
        ]);
    }

    #[Route('/api/template-categories/{id}', name: 'api_template_categories_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $category = $this->findCategory($id);
        return $category instanceof JsonResponse ? $category : new JsonResponse($category->toArray());
    }

    #[Route('/api/template-categories/{id}', name: 'api_template_categories_update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $category = $this->findCategory($id);
        if ($category instanceof JsonResponse) {
            return $category;
        }

        $name = $this->parseName($request);
        if ($name instanceof JsonResponse) {
            return $name;
        }

        if ($category->name() !== $name && $this->categories->findOneBy(['name' => $name]) !== null) {
            return $this->duplicateName();
        }

        $category->rename($name);
        try {
            $this->categories->save($category);
        } catch (UniqueConstraintViolationException) {
            return $this->duplicateName();
        }

        return new JsonResponse($category->toArray());
    }

    #[Route('/api/template-categories/{id}', name: 'api_template_categories_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $category = $this->findCategory($id);
        if ($category instanceof JsonResponse) {
            return $category;
        }

        $this->categories->delete($category);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    private function findCategory(string $id): MailTemplateCategory|JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(['message' => 'Identifiant invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->categories->find(Uuid::fromString($id))
            ?? new JsonResponse(['message' => 'Catégorie introuvable.'], JsonResponse::HTTP_NOT_FOUND);
    }

    private function parseName(Request $request): string|JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['message' => 'JSON invalide.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!is_object($payload) || !property_exists($payload, 'name') || !is_string($payload->name)) {
            return new JsonResponse(['message' => 'Le nom de la catégorie est obligatoire.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $name = trim($payload->name);
        if ($name === '' || mb_strlen($name) > 120) {
            return new JsonResponse(['message' => 'Le nom de la catégorie doit contenir entre 1 et 120 caractères.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $name;
    }

    private function duplicateName(): JsonResponse
    {
        return new JsonResponse(['message' => 'Une catégorie porte déjà ce nom.'], JsonResponse::HTTP_CONFLICT);
    }
}
