<?php

declare(strict_types=1);

namespace App\Command\Message;

final readonly class SendApplicationMessage
{
    public function __construct(
        public string $applicationId,
    ) {
    }
}
