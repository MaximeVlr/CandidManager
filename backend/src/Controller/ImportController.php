<?php

declare(strict_types=1);

namespace App\Controller;

use App\Command\Application\ImportApplicationsCommand;
use App\Service\ImportApplicationsHandler;
use App\EventSubscriber\Exception\InvalidApplicationsJsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ImportController
{
    public function __construct(
        private ImportApplicationsHandler $importApplications,
    ) {
    }

    #[Route('/api/import', name: 'api_import_applications', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = ($this->importApplications)(new ImportApplicationsCommand($request->getContent()));
        } catch (InvalidApplicationsJsonException $exception) {
            return new JsonResponse([
                'message' => 'Le fichier JSON ne respecte pas le format attendu.',
                'errors' => $exception->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($result->toArray(), JsonResponse::HTTP_CREATED);
    }
}
