<?php

declare(strict_types=1);

namespace App\Command\Application;

use Symfony\Component\Uid\Uuid;

final readonly class DeleteApplicationCommand
{
    public function __construct(
        public Uuid $id,
    ) {
    }
}
