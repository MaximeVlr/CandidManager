<?php

declare(strict_types=1);

namespace App\Command\Application;

use App\DTO\ApplicationUpdateRequest;
use Symfony\Component\Uid\Uuid;

final readonly class UpdateApplicationCommand
{
    public function __construct(
        public Uuid $id,
        public ApplicationUpdateRequest $request,
    ) {
    }
}
