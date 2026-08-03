<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ApplicationPreviewResponse;
use App\Repository\JobApplicationRepositoryInterface;
use App\Repository\MailTemplateRepositoryInterface;
use App\Service\ApplicationPreviewRenderer;
use Symfony\Component\Uid\Uuid;

final readonly class PreviewApplicationHandler
{
    public function __construct(
        private JobApplicationRepositoryInterface $jobApplications,
        private MailTemplateRepositoryInterface $mailTemplates,
        private ApplicationPreviewRenderer $renderer,
    ) {
    }

    public function __invoke(Uuid $id): ?ApplicationPreviewResponse
    {
        $jobApplication = $this->jobApplications->findById($id);
        $template = $this->mailTemplates->findDefault();

        if ($jobApplication === null || $template === null) {
            return null;
        }

        return $this->renderer->render($jobApplication, $template);
    }
}
