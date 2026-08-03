<?php

declare(strict_types=1);

namespace App\Service;

use App\Command\Application\CreateApplicationCommand;
use App\Entity\JobApplication;
use App\Repository\JobApplicationRepositoryInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class CreateApplicationHandler
{
    public function __construct(
        private JobApplicationRepositoryInterface $jobApplications,
    ) {
    }

    public function __invoke(CreateApplicationCommand $command): ?JobApplication
    {
        $request = $command->request;
        $jobApplication = new JobApplication(
            $request->company,
            $request->location,
            $request->email,
            $request->subject,
            $request->customMessage,
            $request->officialSourceUrl,
            $request->followUp,
        );

        try {
            $this->jobApplications->save($jobApplication);
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        return $jobApplication;
    }
}
