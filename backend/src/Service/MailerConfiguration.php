<?php

declare(strict_types=1);

namespace App\Service;

final readonly class MailerConfiguration
{
    public function __construct(
        public string $from,
        public string $fromName,
        public bool $sendEnabled,
        public int $maxBatchSize,
        public bool $dryRun,
    ) {}

    public function canSendRealEmails(): bool
    {
        return $this->sendEnabled && !$this->dryRun;
    }
}
