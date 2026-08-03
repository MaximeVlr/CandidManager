<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MailTemplateRepositoryInterface;
use App\Service\MailTemplateValidator;

final readonly class ListTemplatesHandler
{
    public function __construct(
        private MailTemplateRepositoryInterface $mailTemplates,
        private MailTemplateValidator $validator,
    ) {
    }

    /**
     * @return array{items: list<array<string, mixed>>, allowed_variables: list<string>}
     */
    public function __invoke(): array
    {
        return [
            'items' => array_map(
                static fn ($template): array => $template->toArray(),
                $this->mailTemplates->findAllTemplates(),
            ),
            'allowed_variables' => $this->validator->allowedVariables(),
        ];
    }
}
