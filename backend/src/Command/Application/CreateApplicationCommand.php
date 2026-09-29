<?php

declare(strict_types=1);

namespace App\Command\Application;

use App\DTO\ApplicationUpdateRequest;
use App\Entity\MailTemplate;

final readonly class CreateApplicationCommand
{
    public function __construct(
        public ApplicationUpdateRequest $request,
        public ?MailTemplate $template = null,
    ) {
    }
}
