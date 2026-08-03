<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MailTemplate;
use App\Repository\MailTemplateRepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class AttachCvToTemplateHandler
{
    public function __construct(
        private MailTemplateRepositoryInterface $mailTemplates,
        private CvAttachmentStorage $cvStorage,
    ) {
    }

    public function __invoke(string $name, UploadedFile $file): MailTemplate
    {
        $template = $this->mailTemplates->findByName($name);

        if ($template === null) {
            throw new \InvalidArgumentException('Template introuvable.');
        }

        $storedFile = $this->cvStorage->store($file);
        $template->attachCv(
            $storedFile['original_name'],
            $storedFile['stored_name'],
            $storedFile['mime_type'],
        );
        $this->mailTemplates->save($template);

        return $template;
    }
}
