<?php

declare(strict_types=1);

namespace App\Command\Application;

use App\Entity\MailTemplate;
use App\Repository\MailTemplateRepositoryInterface;

final readonly class CreateDefaultMailTemplateCommand
{
    public function __construct(
        private MailTemplateRepositoryInterface $mailTemplates,
    ) {
    }

    public function __invoke(): void
    {
        if ($this->mailTemplates->findDefault() !== null) {
            return;
        }

        $html = <<<'HTML'
<p>Bonjour,</p>
<p>{{ custom_message }}</p>
<p>Je vous adresse ma candidature spontanee pour rejoindre {{ company }}.</p>
<p>Cordialement,</p>
HTML;

        $text = <<<'TEXT'
Bonjour,

{{ custom_message }}

Je vous adresse ma candidature spontanee pour rejoindre {{ company }}.

Cordialement,
TEXT;

        $this->mailTemplates->save(new MailTemplate('default', $html, $text));
    }
}
