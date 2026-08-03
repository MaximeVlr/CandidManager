<?php

declare(strict_types=1);

namespace App\Controller;

use App\EventSubscriber\Exception\InvalidApplicationsJsonException;
use App\DTO\MailerSettingsUpdateRequest;
use App\Repository\MailerSettingsRepositoryInterface;
use App\Service\UpdateMailerSettingsHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class MailerSettingsController
{
    public function __construct(
        private MailerSettingsRepositoryInterface $mailerSettings,
        private UpdateMailerSettingsHandler $updateMailerSettings,
    ) {
    }

    #[Route('/api/mailer-settings', name: 'api_mailer_settings_get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse($this->mailerSettings->get()->toArray());
    }

    #[Route('/api/mailer-settings', name: 'api_mailer_settings_update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return new JsonResponse(['message' => 'JSON invalide: '.$exception->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!is_array($payload)) {
            return new JsonResponse(['message' => 'La racine doit etre un objet.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $settings = ($this->updateMailerSettings)(MailerSettingsUpdateRequest::fromArray($payload));
        } catch (InvalidApplicationsJsonException $exception) {
            return new JsonResponse(['message' => 'Configuration invalide.', 'errors' => $exception->errors()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($settings);
    }
}
