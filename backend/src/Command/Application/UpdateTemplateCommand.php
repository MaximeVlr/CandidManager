<?php

declare(strict_types=1);

namespace App\Command\Application;

use App\DTO\TemplateUpdateRequest;

final readonly class UpdateTemplateCommand
{
    public function __construct(
        public string $name,
        public TemplateUpdateRequest $request,
    ) {
    }
}
