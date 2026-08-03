<?php

declare(strict_types=1);

namespace App\Command\Application;

final readonly class SendBulkApplicationsCommand
{
    /**
     * @param list<string> $ids
     */
    public function __construct(
        public array $ids,
    ) {
    }
}
