<?php

declare(strict_types=1);

namespace App\Service;

use App\Command\Application\UpdateApplicationCommand;
use App\Entity\JobApplication;
use App\Repository\JobApplicationRepositoryInterface;

final readonly class UpdateApplicationHandler
{
    public function __construct(
        private JobApplicationRepositoryInterface $jobApplications,
    ) {
    }

    public function __invoke(UpdateApplicationCommand $command): ?JobApplication
    {
        $jobApplication = $this->jobApplications->findById($command->id);

        if ($jobApplication === null) {
            return null;
        }

        $request = $command->request;
        $jobApplication->updateDetails(
            $request->company,
            $request->location,
            $request->email,
            $request->subject,
            $request->customMessage,
            $request->officialSourceUrl,
            $request->responseStatus,
            $request->followUp,
        );

        $this->jobApplications->save($jobApplication);

        return $jobApplication;
    }
}
