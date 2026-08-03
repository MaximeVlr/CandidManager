<?php

declare(strict_types=1);

namespace App\Service;

use App\Command\Application\ImportApplicationsCommand;
use App\DTO\ImportApplicationsResult;
use App\Entity\JobApplication;
use App\Repository\JobApplicationRepositoryInterface;
use App\Service\ApplicationJsonValidator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class ImportApplicationsHandler
{
    public function __construct(
        private ApplicationJsonValidator $validator,
        private JobApplicationRepositoryInterface $jobApplications,
    ) {
    }

    public function __invoke(ImportApplicationsCommand $command): ImportApplicationsResult
    {
        $items = $this->validator->validate($command->json);
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($items as $index => $item) {
            $jobApplication = new JobApplication(
                $item->company,
                $item->location,
                $item->email,
                $item->subject,
                $item->customMessage,
                $item->officialSourceUrl,
                $item->followUp,
            );

            if ($item->sent) {
                $jobApplication->markAsAlreadySent();
            }

            try {
                $this->jobApplications->save($jobApplication);
                ++$created;
            } catch (UniqueConstraintViolationException) {
                ++$skipped;
                $errors[] = [
                    'index' => $index,
                    'field' => 'email',
                    'message' => 'Candidature deja importee pour cette entreprise et cet email.',
                ];
            }
        }

        return new ImportApplicationsResult($created, $skipped, $errors);
    }
}
