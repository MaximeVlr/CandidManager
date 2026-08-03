<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ImportApplicationItem
{
    public function __construct(
        public string $company,
        public string $location,
        public string $email,
        public string $subject,
        public string $customMessage,
        public string $officialSourceUrl,
        public bool $sent,
        public string $response,
        public bool $followUp,
    ) {
    }
}
