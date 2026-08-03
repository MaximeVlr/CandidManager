<?php

declare(strict_types=1);

namespace App\Service;

use App\Command\Application\DeleteApplicationCommand;
use App\Repository\JobApplicationRepositoryInterface;

final readonly class DeleteApplicationHandler
{
    public function __construct(
        private JobApplicationRepositoryInterface $jobApplications,
    ) {
    }

    public function __invoke(DeleteApplicationCommand $command): bool
    {
        $jobApplication = $this->jobApplications->findById($command->id);

        if ($jobApplication === null) {
            return false;
        }

        $this->jobApplications->delete($jobApplication);

        return true;
    }
}
