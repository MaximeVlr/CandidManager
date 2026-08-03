<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\JobApplication;

final readonly class SendApplicationResult
{
    public function __construct(
        public bool $sent,
        public string $message,
        public ?JobApplication $jobApplication,
    ) {
    }

    /**
     * @return array{sent: bool, message: string, application: array<string, mixed>|null}
     */
    public function toArray(): array
    {
        return [
            'sent' => $this->sent,
            'message' => $this->message,
            'application' => $this->jobApplication?->toArray(),
        ];
    }
}
