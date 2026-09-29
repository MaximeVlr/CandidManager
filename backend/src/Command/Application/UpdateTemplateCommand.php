<?php

declare(strict_types=1);

namespace App\Command\Application;

use App\DTO\TemplateUpdateRequest;
use App\Entity\MailTemplateCategory;

final readonly class UpdateTemplateCommand
{
    public function __construct(
        public string $name,
        public TemplateUpdateRequest $request,
        public ?MailTemplateCategory $category = null,
    ) {
    }
}
