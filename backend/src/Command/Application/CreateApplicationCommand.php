<?php

declare(strict_types=1);

namespace App\Command\Application;

use App\DTO\ApplicationUpdateRequest;

final readonly class CreateApplicationCommand
{
    public function __construct(
        public ApplicationUpdateRequest $request,
    ) {
    }
}
