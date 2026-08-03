<?php

declare(strict_types=1);

namespace App\Service;

use App\Command\Application\UpdateTemplateCommand;
use App\Entity\MailTemplate;
use App\Repository\MailTemplateRepositoryInterface;
use App\Service\MailTemplateValidator;

final readonly class UpdateTemplateHandler
{
    public function __construct(
        private MailTemplateRepositoryInterface $mailTemplates,
        private MailTemplateValidator $validator,
    ) {
    }

    public function __invoke(UpdateTemplateCommand $command): MailTemplate
    {
        $this->validator->validate($command->request->htmlBody, $command->request->textBody);

        $template = $this->mailTemplates->findByName($command->name) ?? new MailTemplate(
            $command->name,
            $command->request->htmlBody,
            $command->request->textBody,
        );

        $template->updateBodies($command->request->htmlBody, $command->request->textBody);
        $this->mailTemplates->save($template);

        return $template;
    }
}
