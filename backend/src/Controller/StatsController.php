<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ApplicationStatsCalculator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class StatsController
{
    public function __construct(
        private ApplicationStatsCalculator $statsCalculator,
    ) {
    }

    #[Route('/api/stats', name: 'api_stats', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->statsCalculator->calculate());
    }
}
