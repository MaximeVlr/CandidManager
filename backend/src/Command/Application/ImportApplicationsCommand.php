<?php

declare(strict_types=1);

namespace App\Command\Application;

final readonly class ImportApplicationsCommand
{
    public function __construct(
        public string $json,
    ) {
    }
}
