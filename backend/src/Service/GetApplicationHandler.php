<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\JobApplication;
use App\Repository\JobApplicationRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final readonly class GetApplicationHandler
{
    public function __construct(
        private JobApplicationRepositoryInterface $jobApplications,
    ) {
    }

    public function __invoke(Uuid $id): ?JobApplication
    {
        return $this->jobApplications->findById($id);
    }
}
